<?php

namespace App\Controllers\Seller;

use App\Controllers\Concerns\SavesProductCatalog;
use App\Controllers\BaseController;
use App\Models\CategoryModel;
use App\Models\ProductImageModel;
use App\Models\ProductModel;

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
        $sellerId = (int) session()->get('user.id');
        $products = $this->productModel
            ->select('products.*, categories.name as category_name, (SELECT image_path FROM product_images WHERE product_images.product_id = products.id ORDER BY is_primary DESC, id ASC LIMIT 1) as primary_image')
            ->join('categories', 'categories.id = products.category_id', 'left')
            ->where('products.seller_id', $sellerId)
            ->orderBy('products.id', 'DESC')
            ->paginate(50);

        return view('seller/products/index', [
            'title'    => 'Manage Products — Solqam Seller Hub',
            'products' => $products,
            'pager'    => $this->productModel->pager,
        ]);
    }

    public function create()
    {
        $categories = $this->categoryModel->getActiveCategories();

        return view('seller/products/create', [
            'title'      => 'Add New Product — Solqam Seller Hub',
            'categories' => $categories,
        ]);
    }

    public function store()
    {
        $sellerId = (int) session()->get('user.id');

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

        $productId = $this->productModel->insert(array_merge($this->catalogProductPayload(), [
            'seller_id' => $sellerId,
            'name'      => $name,
            'slug'      => $slug,
            'sku'       => $this->request->getPost('sku') ?: ('SKU-' . strtoupper(substr(md5(uniqid()), 0, 6))),
            'status'    => 'active',
        ]));

        $this->savePrimaryImage((int) $productId);
        $this->saveGalleryImages((int) $productId, true);
        $this->saveVariants((int) $productId);

        return redirect()->to('/seller/products')->with('success', 'Product published successfully!');
    }

    public function edit($id)
    {
        $sellerId = (int) session()->get('user.id');
        $product = $this->productModel->where('id', $id)->where('seller_id', $sellerId)->first();

        if (!$product) {
            return redirect()->to('/seller/products')->with('error', 'Product not found.');
        }

        $categories = $this->categoryModel->getActiveCategories();
        $images = $this->productImageModel->where('product_id', $id)->findAll();
        $variants = (new \App\Models\ProductVariantModel())->forProduct((int) $id);

        return view('seller/products/edit', [
            'title'      => 'Edit Product — Solqam Seller Hub',
            'product'    => $product,
            'categories' => $categories,
            'images'     => $images,
            'variants'   => $variants,
        ]);
    }

    public function update($id)
    {
        $sellerId = (int) session()->get('user.id');
        $product = $this->productModel->where('id', $id)->where('seller_id', $sellerId)->first();

        if (!$product) {
            return redirect()->to('/seller/products')->with('error', 'Product not found.');
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

        $this->productModel->update($id, array_merge($this->catalogProductPayload(), [
            'name'   => $this->request->getPost('name'),
            'sku'    => $this->request->getPost('sku'),
            'status' => $this->request->getPost('status'),
        ]));

        $this->replacePrimaryImage((int) $id);
        $this->saveGalleryImages((int) $id, true);
        $this->replaceVariants((int) $id);

        return redirect()->to('/seller/products')->with('success', 'Product updated successfully.');
    }

    public function quickStock($id)
    {
        $sellerId = (int) session()->get('user.id');
        $product = $this->productModel->where('id', $id)->where('seller_id', $sellerId)->first();
        if (!$product) {
            return $this->redirectAfterHub('/seller/products', 'error', 'Product not found.');
        }

        $stock = max(0, (int) $this->request->getPost('stock'));
        $status = $this->request->getPost('status');
        $data = ['stock' => $stock];
        if (in_array($status, ['active', 'inactive'], true)) {
            $data['status'] = $status;
        }
        $this->productModel->update((int) $id, $data);

        return $this->redirectAfterHub('/seller/products', 'success', 'Product stock/status updated.');
    }

    public function delete($id)
    {
        $sellerId = (int) session()->get('user.id');
        $product = $this->productModel->where('id', $id)->where('seller_id', $sellerId)->first();

        if ($product) {
            $this->productModel->delete($id);
            return redirect()->to('/seller/products')->with('success', 'Product deleted.');
        }

        return redirect()->to('/seller/products')->with('error', 'Product not found.');
    }

    public function csvTemplate()
    {
        return $this->response
            ->setHeader('Content-Type', 'text/csv')
            ->setHeader('Content-Disposition', 'attachment; filename="solqam-products.csv"')
            ->setBody("name,category_id,price,stock,sku,description,brand,cashback_percent\nWireless Earbuds,1,2499,20,SKU-EAR-01,Bluetooth earbuds,Solqam,5\n");
    }

    public function importCsv()
    {
        $sellerId = (int) session()->get('user.id');
        $file = $this->request->getFile('csv');
        if (! $file || ! $file->isValid()) {
            return redirect()->back()->with('error', 'Please choose a CSV file.');
        }

        $handle = fopen($file->getTempName(), 'r');
        if (! $handle) {
            return redirect()->back()->with('error', 'The CSV file could not be read.');
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            return redirect()->back()->with('error', 'CSV empty hai.');
        }
        $header = array_map(static fn ($h) => strtolower(trim((string) $h)), $header);
        $imported = 0;
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 4) {
                continue;
            }
            $map = @array_combine($header, array_pad($row, count($header), ''));
            if (! is_array($map)) {
                continue;
            }
            $name = trim((string) ($map['name'] ?? ''));
            $categoryId = (int) ($map['category_id'] ?? 0);
            $price = (float) ($map['price'] ?? 0);
            $stock = (int) ($map['stock'] ?? 0);
            if ($name === '' || $categoryId <= 0 || $price <= 0) {
                continue;
            }
            $this->productModel->insert([
                'seller_id'        => $sellerId,
                'category_id'      => $categoryId,
                'name'             => $name,
                'slug'             => url_title($name, '-', true) . '-' . substr(md5(uniqid('', true)), 0, 5),
                'description'      => (string) ($map['description'] ?? $name),
                'price'            => $price,
                'stock'            => max(0, $stock),
                'sku'              => (string) ($map['sku'] ?? ('SKU-' . strtoupper(substr(md5(uniqid('', true)), 0, 6)))),
                'brand'            => (string) ($map['brand'] ?? ''),
                'cashback_percent' => max(0, min(100, (float) ($map['cashback_percent'] ?? 0))),
                'status'           => 'active',
            ]);
            $imported++;
        }
        fclose($handle);

        return redirect()->to('/seller/products')->with('success', $imported . ' products imported.');
    }
}
