<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Controllers\Concerns\SavesProductCatalog;
use App\Models\CategoryModel;
use App\Models\ProductImageModel;
use App\Models\ProductModel;
use App\Models\ProductVariantModel;

/**
 * Admin First-Party Product Controller
 * Manages products listed directly by the Platform Administrator.
 * Admin's user.id is used as seller_id so products flow through the same
 * order pipeline as regular seller products.
 */
class MyProductController extends BaseController
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
        $adminId  = (int) session()->get('user.id');
        $products = $this->productModel
            ->select('products.*, categories.name as category_name, (SELECT image_path FROM product_images WHERE product_images.product_id = products.id ORDER BY is_primary DESC, id ASC LIMIT 1) as primary_image')
            ->join('categories', 'categories.id = products.category_id', 'left')
            ->where('products.seller_id', $adminId)
            ->orderBy('products.id', 'DESC')
            ->paginate(50);

        return view('admin/my-products/index', [
            'title'    => 'My Products — Solqam Admin',
            'products' => $products,
            'pager'    => $this->productModel->pager,
        ]);
    }

    public function create()
    {
        $categories = $this->categoryModel->optionsForSelect();

        return view('admin/my-products/create', [
            'title'      => 'Add New Product — Admin Store',
            'categories' => $categories,
        ]);
    }

    public function store()
    {
        $adminId = (int) session()->get('user.id');

        $rules = [
            'name'        => 'required|min_length[3]|max_length[255]',
            'category_id' => 'required|numeric',
            'price'       => 'required|numeric',
            'stock'       => 'required|integer',
            'description' => 'required',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $name = $this->request->getPost('name');
        $slug = url_title($name, '-', true) . '-' . substr(md5(uniqid()), 0, 5);

        try {
            $payload = array_merge($this->catalogProductPayload(), [
                'seller_id' => $adminId,
                'name'      => $name,
                'slug'      => $slug,
                'sku'       => $this->request->getPost('sku') ?: ('ADM-' . strtoupper(substr(md5(uniqid()), 0, 6))),
                'status'    => 'active',
            ]);
            if ((int) ($payload['category_id'] ?? 0) <= 0) {
                return redirect()->back()->withInput()->with('error', 'Select a valid category. Add one first: Admin → Categories.');
            }
            $productId = $this->productModel->insertCatalog($payload);
            if ($productId <= 0) {
                return redirect()->back()->withInput()->with('error', 'Product could not be saved. Check category and try again.');
            }
            $this->persistPostedCashback($productId);
            $this->savePrimaryImage($productId);
            $this->saveGalleryImages($productId, true);
            $this->saveVariants($productId);
        } catch (\Throwable $e) {
            log_message('error', 'Admin add product: ' . $e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Product could not be saved: ' . $e->getMessage());
        }

        return redirect()->to('/admin/my-products')->with('success', 'Product listed on marketplace successfully!');
    }

    public function edit($id)
    {
        $adminId = (int) session()->get('user.id');
        $product = $this->productModel->where('id', $id)->where('seller_id', $adminId)->first();

        if (!$product) {
            return redirect()->to('/admin/my-products')->with('error', 'Product not found or access denied.');
        }

        $categories = $this->categoryModel->optionsForSelect();
        $images     = $this->productImageModel->forProduct((int) $id);
        $variants   = (new ProductVariantModel())->forProduct((int) $id);

        return view('admin/my-products/edit', [
            'title'      => 'Edit Product — Admin Store',
            'product'    => $product,
            'categories' => $categories,
            'images'     => $images,
            'variants'   => $variants,
        ]);
    }

    public function update($id)
    {
        $adminId = (int) session()->get('user.id');
        $product = $this->productModel->where('id', $id)->where('seller_id', $adminId)->first();

        if (!$product) {
            return redirect()->to('/admin/my-products')->with('error', 'Product not found.');
        }

        $rules = [
            'name'        => 'required|min_length[3]|max_length[255]',
            'category_id' => 'required|numeric',
            'price'       => 'required|numeric',
            'stock'       => 'required|integer',
            'status'      => 'required|in_list[active,inactive,archived]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $this->productModel->updateCatalog((int) $id, array_merge($this->catalogProductPayload(), [
            'name'   => $this->request->getPost('name'),
            'sku'    => $this->request->getPost('sku'),
            'status' => $this->request->getPost('status'),
        ]));
        $this->persistPostedCashback((int) $id);

        $this->replacePrimaryImage((int) $id);
        $this->saveGalleryImages((int) $id, true);
        $this->replaceVariants((int) $id);

        return redirect()->to('/admin/my-products')->with('success', 'Product updated successfully.');
    }

    public function delete($id)
    {
        $adminId = (int) session()->get('user.id');
        $product = $this->productModel->where('id', $id)->where('seller_id', $adminId)->first();

        if ($product) {
            $this->productModel->delete($id);
            return redirect()->to('/admin/my-products')->with('success', 'Product removed from marketplace.');
        }

        return redirect()->to('/admin/my-products')->with('error', 'Product not found.');
    }
}
