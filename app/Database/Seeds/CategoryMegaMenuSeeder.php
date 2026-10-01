<?php

namespace App\Database\Seeds;

use App\Models\CategoryModel;
use CodeIgniter\Database\Seeder;

class CategoryMegaMenuSeeder extends Seeder
{
    public function run()
    {
        $model = new CategoryModel();

        $electronics = $this->ensure($model, 'Electronics', null, 'bi-phone');
        $mobiles     = $this->ensure($model, 'Mobiles', $electronics, 'bi-phone');
        $phones      = $this->ensure($model, 'Smartphones', $mobiles);
        $this->ensure($model, 'Samsung', $phones);
        $this->ensure($model, 'Apple', $phones);
        $this->ensure($model, 'Xiaomi', $phones);
        $this->ensure($model, 'Infinix', $phones);
        $this->ensure($model, 'Feature Phones', $mobiles);
        $this->ensure($model, 'Tablets', $mobiles);

        $computers = $this->ensure($model, 'Laptops & Computers', $electronics, 'bi-laptop');
        $laptops   = $this->ensure($model, 'Laptops', $computers);
        $this->ensure($model, 'Gaming Laptops', $laptops);
        $this->ensure($model, 'Business Laptops', $laptops);
        $this->ensure($model, 'Desktops', $computers);
        $this->ensure($model, 'Computer Accessories', $computers);

        $audio = $this->ensure($model, 'Audio', $electronics, 'bi-headphones');
        $this->ensure($model, 'Earbuds', $audio);
        $this->ensure($model, 'Headphones', $audio);

        $fashion = $this->ensure($model, 'Fashion', null, 'bi-handbag');
        $mens    = $this->ensure($model, "Men's Fashion", $fashion);
        $this->ensure($model, 'Shirts', $mens);
        $this->ensure($model, 'Shoes', $mens);
        $womens  = $this->ensure($model, "Women's Fashion", $fashion);
        $this->ensure($model, 'Unstitched', $womens);
        $this->ensure($model, 'Pret', $womens);
    }

    protected function ensure(CategoryModel $model, string $name, ?int $parentId, string $icon = 'bi-grid'): int
    {
        $slug = url_title($name, '-', true);
        $row  = $model->where('slug', $slug)->first();
        if ($row) {
            if ($parentId && empty($row['parent_id'])) {
                $model->update($row['id'], ['parent_id' => $parentId]);
            }

            return (int) $row['id'];
        }

        $payload = [
            'name'        => $name,
            'slug'        => $model->uniqueSlug($name),
            'parent_id'   => $parentId,
            'icon'        => $icon,
            'is_active'   => 1,
            'sort_order'  => 0,
        ];
        if ($model->db->fieldExists('commission_percent', 'categories')) {
            $payload['commission_percent'] = $parentId ? null : 10.00;
        }
        $model->insert($payload);

        return (int) $model->getInsertID();
    }
}
