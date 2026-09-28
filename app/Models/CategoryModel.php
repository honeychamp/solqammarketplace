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

    public function getTree(): array
    {
        $all = $this->getActiveCategories();
        $byParent = [];
        foreach ($all as $cat) {
            $pid = (int) ($cat['parent_id'] ?? 0);
            $byParent[$pid][] = $cat;
        }

        $tree = [];
        foreach ($byParent[0] ?? $byParent[''] ?? [] as $parent) {
            $parent['children'] = $byParent[(int) $parent['id']] ?? [];
            $tree[] = $parent;
        }

        if (empty($tree) && !empty($all)) {
            foreach ($all as $cat) {
                $cat['children'] = [];
                $tree[] = $cat;
            }
        }

        return $tree;
    }

    public function getSelfAndDescendantIds(int $categoryId): array
    {
        $ids = [$categoryId];
        $children = $this->where('parent_id', $categoryId)->findAll();
        foreach ($children as $child) {
            $ids = array_merge($ids, $this->getSelfAndDescendantIds((int) $child['id']));
        }
        return array_values(array_unique($ids));
    }
}
