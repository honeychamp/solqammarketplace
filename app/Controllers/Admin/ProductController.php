<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Controllers\Concerns\SavesProductCatalog;
use App\Models\CategoryModel;
use App\Models\ProductImageModel;
use App\Models\ProductModel;
use App\Models\ProductVariantModel;

class ProductController extends BaseController
{
    use SavesProductCatalog;

    protected ProductModel $productModel;
    protected ProductImageModel $productImageModel;
    protected CategoryModel $categoryModel;

    public function __construct()
    {
        $this->productModel      = new ProductModel();
        $this->productImageModel = new ProductImageModel();
        $this->categoryModel     = new CategoryModel();
    }

    public function index()
    {
        $products = $this->productModel
            ->select('products.*, categories.name as category_name, users.name as seller_name, seller_profiles.store_name, (SELECT image_path FROM product_images WHERE product_images.product_id = products.id ORDER BY is_primary DESC, id ASC LIMIT 1) as primary_image')
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

    public function edit($id)
    {
        $product = $this->productModel->find((int) $id);
        if (! $product) {
            return redirect()->to('/admin/products')->with('error', 'Product not found.');
        }

        return view('admin/my-products/edit', [
            'title'        => 'Edit Product — Solqam Admin',
            'product'      => $product,
            'categories'   => $this->categoryModel->optionsForSelect(),
            'images'       => $this->productImageModel->forProduct((int) $id),
            'variants'     => (new ProductVariantModel())->forProduct((int) $id),
            'updateAction' => site_url('admin/products/update/' . $id),
            'backUrl'      => site_url('admin/products'),
            'backLabel'    => 'Back to all products',
        ]);
    }

    public function update($id)
    {
        $product = $this->productModel->find((int) $id);
        if (! $product) {
            return redirect()->to('/admin/products')->with('error', 'Product not found.');
        }

        if ($this->postedEmptyForm()) {
            return redirect()->back()->with('error', 'Save failed. The form arrived empty — usually a video/image is larger than the PHP upload limit.');
        }

        $rules = [
            'name'        => 'required|min_length[3]|max_length[255]',
            'category_id' => 'required|numeric',
            'price'       => 'required|numeric',
            'stock'       => 'required|integer',
            'status'      => 'permit_empty|in_list[active,inactive,archived]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $payload = array_merge($this->catalogProductPayload(), [
                'name'   => $this->request->getPost('name'),
                'sku'    => $this->request->getPost('sku'),
                'status' => $this->request->getPost('status') ?: ($product['status'] ?? 'active'),
            ]);
            if ((int) ($payload['category_id'] ?? 0) <= 0) {
                return redirect()->back()->withInput()->with('error', 'Select a valid category.');
            }
            $this->persistCatalogUpdate((int) $id, $product, $payload);
        } catch (\Throwable $e) {
            log_message('error', 'Admin catalog edit product: ' . $e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Product could not be updated: ' . $e->getMessage());
        }

        return redirect()->to('/admin/products')->with('success', 'Product updated successfully.');
    }

    public function delete($id)
    {
        $product = $this->productModel->find((int) $id);
        if (! $product) {
            return redirect()->to('/admin/products')->with('error', 'Product not found.');
        }
        $this->productModel->delete((int) $id);

        return redirect()->to('/admin/products')->with('success', 'Product removed.');
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
