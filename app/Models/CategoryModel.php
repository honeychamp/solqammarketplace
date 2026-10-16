<?php

namespace App\Models;

use CodeIgniter\Model;

class CategoryModel extends Model
{
    protected $table            = 'categories';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'parent_id',
        'name',
        'slug',
        'description',
        'image',
        'sort_order',
        'icon',
        'commission_percent',
        'is_active',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getActiveCategories(): array
    {
        return $this->where('is_active', 1)->orderBy('sort_order', 'ASC')->orderBy('name', 'ASC')->findAll();
    }

    public function getTree(int $maxDepth = 4): array
    {
        $all = $this->getActiveCategories();
        $byParent = [];
        foreach ($all as $cat) {
            $pid = (int) ($cat['parent_id'] ?? 0);
            $byParent[$pid][] = $cat;
        }

        $attach = static function (array $nodes, int $depth) use (&$attach, $byParent, $maxDepth): array {
            $out = [];
            foreach ($nodes as $node) {
                $node['depth']    = $depth;
                $node['children'] = [];
                if ($depth < $maxDepth) {
                    $kids = $byParent[(int) $node['id']] ?? [];
                    $node['children'] = $attach($kids, $depth + 1);
                }
                $out[] = $node;
            }

            return $out;
        };

        $roots = $byParent[0] ?? [];
        if ($roots === [] && $all !== []) {
            foreach ($all as $cat) {
                $cat['depth']    = 1;
                $cat['children'] = [];
                $roots[]         = $cat;
            }

            return $roots;
        }

        return $attach($roots, 1);
    }

    public function optionsForSelect(): array
    {
        $rows = [];
        $walk = static function (array $nodes, string $prefix) use (&$walk, &$rows): void {
            foreach ($nodes as $node) {
                $rows[] = [
                    'id'    => $node['id'],
                    'name'  => $node['name'],
                    'label' => $prefix . $node['name'],
                    'depth' => (int) ($node['depth'] ?? 1),
                    'slug'  => $node['slug'] ?? '',
                ];
                $walk($node['children'] ?? [], $prefix . '— ');
            }
        };
        $walk($this->getTree(4), '');

        return $rows;
    }

    public function optionsForParent(?int $excludeId = null): array
    {
        $exclude = [];
        if ($excludeId) {
            $exclude = array_flip($this->getSelfAndDescendantIds($excludeId));
        }

        $out = [];
        foreach ($this->optionsForSelect() as $row) {
            if (isset($exclude[(int) $row['id']])) {
                continue;
            }
            if ((int) $row['depth'] >= 4) {
                continue;
            }
            $out[] = $row;
        }

        return $out;
    }

    public function depthOf(?int $categoryId): int
    {
        return count($this->breadcrumb((int) $categoryId));
    }

    public function catalogIds(?string $slug = null, $id = null): array
    {
        $cat = null;
        if ($slug !== null && $slug !== '') {
            $cat = $this->db->table($this->table)->where('slug', $slug)->get()->getRowArray();
        } elseif ($id) {
            $cat = $this->db->table($this->table)->where('id', (int) $id)->get()->getRowArray();
        }
        if (! $cat) {
            return [];
        }

        return $this->getSelfAndDescendantIds((int) $cat['id']);
    }

    protected array $rowIndex = [];

    protected function rowIndex(): array
    {
        if ($this->rowIndex === []) {
            foreach ($this->db->table($this->table)->select('id, parent_id, name, slug')->get()->getResultArray() as $row) {
                $this->rowIndex[(int) $row['id']] = $row;
            }
        }

        return $this->rowIndex;
    }

    public function breadcrumb(int $categoryId): array
    {
        if ($categoryId <= 0) {
            return [];
        }

        $byId  = $this->rowIndex();
        $chain = [];
        $pid   = $categoryId;
        $guard = 0;
        while ($pid && isset($byId[$pid]) && $guard++ < 8) {
            array_unshift($chain, $byId[$pid]);
            $pid = (int) ($byId[$pid]['parent_id'] ?? 0);
        }

        return $chain;
    }

    public function withPaths(array $rows): array
    {
        foreach ($rows as &$row) {
            $crumbs = $this->breadcrumb((int) ($row['id'] ?? 0));
            $row['breadcrumb'] = $crumbs;
            $row['path_label'] = implode(' → ', array_column($crumbs, 'name'));
            $row['depth']      = $crumbs === [] ? 1 : count($crumbs);
        }
        unset($row);

        return $rows;
    }

    public function exists(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        return (bool) $this->db->table($this->table)->select('id')->where('id', $id)->get()->getFirstRow();
    }

    public function getSelfAndDescendantIds(int $categoryId): array
    {
        if ($categoryId <= 0) {
            return [];
        }

        $byId     = $this->rowIndex();
        $children = [];
        foreach ($byId as $id => $row) {
            $children[(int) ($row['parent_id'] ?? 0)][] = (int) $id;
        }

        $ids   = [$categoryId];
        $queue = [$categoryId];
        $guard = 0;
        while ($queue !== [] && $guard++ < 5000) {
            $current = array_pop($queue);
            foreach ($children[$current] ?? [] as $childId) {
                $ids[]   = $childId;
                $queue[] = $childId;
            }
        }

        return array_values(array_unique($ids));
    }

    public function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = url_title($name, '-', true);
        if ($base === '') {
            $base = 'category';
        }

        $slug = $base;
        $n    = 2;
        while ($n < 500) {
            try {
                $sql    = 'SELECT id FROM `' . $this->table . '` WHERE `slug` = ?';
                $binds  = [$slug];
                if ($ignoreId !== null) {
                    $sql    .= ' AND `id` != ?';
                    $binds[] = $ignoreId;
                }
                $sql .= ' LIMIT 1';
                if ($this->db->query($sql, $binds)->getFirstRow() === null) {
                    return $slug;
                }
            } catch (\Throwable $e) {
                return $base . '-' . bin2hex(random_bytes(3));
            }
            $slug = $base . '-' . $n;
            $n++;
        }

        return $base . '-' . bin2hex(random_bytes(3));
    }

    public function insertCategory(array $data): int
    {
        $row = $this->onlyExistingColumns($data);
        if (($row['name'] ?? '') === '') {
            throw new \RuntimeException('Category name is required.');
        }
        if (empty($row['slug'])) {
            $row['slug'] = $this->uniqueSlug((string) $row['name']);
        }
        if (! isset($row['is_active']) && $this->hasColumn('is_active')) {
            $row['is_active'] = 1;
        }
        if (! isset($row['sort_order']) && $this->hasColumn('sort_order')) {
            $max = $this->db->table($this->table)->selectMax('sort_order')->get()->getRowArray();
            $row['sort_order'] = (int) ($max['sort_order'] ?? 0) + 1;
        }
        $now = date('Y-m-d H:i:s');
        if ($this->hasColumn('created_at')) {
            $row['created_at'] = $now;
        }
        if ($this->hasColumn('updated_at')) {
            $row['updated_at'] = $now;
        }

        $ok = $this->db->table($this->table)->insert($row);
        $id = (int) $this->db->insertID();
        if (! $ok || $id < 1) {
            $err = $this->db->error();
            throw new \RuntimeException($err['message'] ?: 'Could not save the category.');
        }

        $this->rowIndex = [];

        return $id;
    }

    public function updateCategory(int $id, array $data): void
    {
        $row = $this->onlyExistingColumns($data);
        if ($row === []) {
            throw new \RuntimeException('Nothing to update.');
        }
        if ($this->hasColumn('updated_at')) {
            $row['updated_at'] = date('Y-m-d H:i:s');
        }

        $this->db->table($this->table)->where('id', $id)->update($row);
        $err = $this->db->error();
        if (! empty($err['code']) && (int) $err['code'] !== 0) {
            throw new \RuntimeException($err['message'] ?: 'Could not update the category.');
        }

        $this->rowIndex = [];
    }

    protected function onlyExistingColumns(array $data): array
    {
        $out = [];
        $nullable = ['parent_id', 'commission_percent', 'icon', 'image', 'description'];
        foreach ($data as $key => $value) {
            if (! $this->hasColumn($key)) {
                continue;
            }
            if ($value === '' && in_array($key, $nullable, true)) {
                $out[$key] = null;
                continue;
            }
            $out[$key] = $value;
        }

        return $out;
    }

    protected function hasColumn(string $column): bool
    {
        static $fields = null;
        if ($fields === null) {
            try {
                $fields = $this->db->getFieldNames($this->table);
            } catch (\Throwable $e) {
                $fields = $this->allowedFields;
            }
        }

        return in_array($column, $fields, true);
    }
}
