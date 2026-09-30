<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ProductModel;

class ProductController extends BaseController
{
    protected ProductModel $productModel;

    public function __construct()
    {
        $this->productModel = new ProductModel();
    }

    public function index()
    {
        $products = $this->productModel
            ->select('products.*, categories.name as category_name, users.name as seller_name, seller_profiles.store_name')
            ->join('categories', 'categories.id = products.category_id', 'left')
            ->join('users', 'users.id = products.seller_id', 'left')
            ->join('seller_profiles', 'seller_profiles.user_id = products.seller_id', 'left')
            ->orderBy('products.id', 'DESC')
            ->paginate(50);

        return view('admin/products/index', [
            'title'    => 'Manage All Products — Solqam Admin Console',
            'products' => $products,
            'pager'    => $this->productModel->pager,
        ]);
    }

    public function toggleStatus($id)
    {
        $product = $this->productModel->find($id);
        if ($product) {
            $newStatus = ($product['status'] === 'active') ? 'inactive' : 'active';
            $this->productModel->update($id, ['status' => $newStatus]);
            return $this->redirectAfterHub('/admin/products', 'success', "Product status changed to {$newStatus}.");
        }
        return $this->redirectAfterHub('/admin/products', 'error', 'Product not found.');
    }
}
