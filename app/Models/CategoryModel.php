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
        $id    = (int) $categoryId;
        $depth = 0;
        $guard = 0;
        while ($id > 0 && $guard++ < 8) {
            $row = $this->db->table($this->table)->select('id, parent_id')->where('id', $id)->get()->getRowArray();
            if (! $row) {
                break;
            }
            $depth++;
            $id = (int) ($row['parent_id'] ?? 0);
        }

        return $depth;
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
        $ids      = [$categoryId];
        $children = $this->db->table($this->table)->select('id')->where('parent_id', $categoryId)->get()->getResultArray();
        foreach ($children as $child) {
            $ids = array_merge($ids, $this->getSelfAndDescendantIds((int) $child['id']));
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
        while (true) {
            $builder = $this->db->table($this->table)->select('id')->where('slug', $slug);
            if ($ignoreId !== null) {
                $builder->where('id !=', $ignoreId);
            }
            if ($builder->get()->getFirstRow() === null) {
                return $slug;
            }
            $slug = $base . '-' . $n;
            $n++;
        }
    }
}
