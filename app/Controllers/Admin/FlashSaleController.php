<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\FlashSaleItemModel;
use App\Models\FlashSaleModel;
use App\Models\ProductModel;

class FlashSaleController extends BaseController
{
    public function index()
    {
        $itemModel = new FlashSaleItemModel();
        $sales = (new FlashSaleModel())->orderBy('id', 'DESC')->findAll();
        $products = (new ProductModel())->where('status', 'active')->orderBy('name', 'ASC')->findAll(120);
        $live = (new FlashSaleModel())->getActive() ?? ($sales[0] ?? null);
        $items = [];
        $pending = [];
        if ($live) {
            $items = $itemModel
                ->select('flash_sale_items.*, products.name, products.price as list_price, users.name as seller_name')
                ->join('products', 'products.id = flash_sale_items.product_id')
                ->join('users', 'users.id = flash_sale_items.seller_id', 'left')
                ->where('flash_sale_id', $live['id'])
                ->where('flash_sale_items.status', 'approved')
                ->findAll();
        }
        $pending = $itemModel
            ->select('flash_sale_items.*, products.name, products.price as list_price, users.name as seller_name, flash_sales.title as campaign_title, flash_sales.campaign_type')
            ->join('products', 'products.id = flash_sale_items.product_id')
            ->join('users', 'users.id = flash_sale_items.seller_id', 'left')
            ->join('flash_sales', 'flash_sales.id = flash_sale_items.flash_sale_id')
            ->where('flash_sale_items.status', 'pending')
            ->orderBy('flash_sale_items.id', 'DESC')
            ->findAll(40);

        return view('admin/flash-sales/index', [
            'title'    => 'Campaigns — Solqam Admin',
            'sales'    => $sales,
            'products' => $products,
            'items'    => $items,
            'pending'  => $pending,
            'active'   => $live,
        ]);
    }

    public function store()
    {
        $type = $this->request->getPost('campaign_type') === 'mega' ? 'mega' : 'flash';
        (new FlashSaleModel())->insert([
            'title'         => $this->request->getPost('title') ?: ($type === 'mega' ? 'Mega Sale' : 'Flash Sale'),
            'campaign_type' => $type,
            'starts_at'     => $this->normalizeDate($this->request->getPost('starts_at')) ?: date('Y-m-d H:i:s'),
            'ends_at'       => $this->normalizeDate($this->request->getPost('ends_at')) ?: date('Y-m-d H:i:s', strtotime('+1 day')),
            'is_active'     => 1,
            'seller_join'   => $this->request->getPost('seller_join') ? 1 : 0,
            'rules_note'    => $this->request->getPost('rules_note'),
        ]);

        return redirect()->to('/admin/flash-sales')->with('success', 'Campaign created. Sellers can join if that option is on.');
    }

    public function addItem()
    {
        $productId = (int) $this->request->getPost('product_id');
        $saleId = (int) $this->request->getPost('flash_sale_id');
        $salePrice = (float) $this->request->getPost('sale_price');
        $product = (new ProductModel())->find($productId);
        if (! $product || $saleId < 1 || $salePrice <= 0) {
            return redirect()->back()->with('error', 'Pick a product and a valid sale price.');
        }
        if ($salePrice >= (float) $product['price']) {
            return redirect()->back()->with('error', 'Deal price must be lower than the listed price.');
        }

        $items = new FlashSaleItemModel();
        $dup = $items->where('flash_sale_id', $saleId)->where('product_id', $productId)->first();
        if ($dup) {
            $items->update($dup['id'], [
                'sale_price' => $salePrice,
                'status'     => 'approved',
                'source'     => 'admin',
                'seller_id'  => (int) $product['seller_id'],
            ]);
        } else {
            $items->insert([
                'flash_sale_id' => $saleId,
                'product_id'    => $productId,
                'seller_id'     => (int) $product['seller_id'],
                'sale_price'    => $salePrice,
                'status'        => 'approved',
                'source'        => 'admin',
            ]);
        }

        return redirect()->to('/admin/flash-sales')->with('success', 'Deal listed (approved).');
    }

    public function approve(int $id)
    {
        (new FlashSaleItemModel())->update($id, ['status' => 'approved']);

        return redirect()->to('/admin/flash-sales')->with('success', 'Seller deal approved.');
    }

    public function reject(int $id)
    {
        (new FlashSaleItemModel())->update($id, ['status' => 'rejected']);

        return redirect()->to('/admin/flash-sales')->with('success', 'Seller deal rejected.');
    }

    protected function normalizeDate(?string $value): ?string
    {
        if (! $value) {
            return null;
        }
        $value = str_replace('T', ' ', $value);
        if (strlen($value) === 16) {
            $value .= ':00';
        }

        return $value;
    }
}
