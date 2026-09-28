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
        $sales = (new FlashSaleModel())->orderBy('id', 'DESC')->findAll();
        $products = (new ProductModel())->where('status', 'active')->orderBy('name', 'ASC')->findAll(80);
        $items = [];
        $active = (new FlashSaleModel())->getActive() ?? ($sales[0] ?? null);
        if ($active) {
            $items = (new FlashSaleItemModel())
                ->select('flash_sale_items.*, products.name')
                ->join('products', 'products.id = flash_sale_items.product_id')
                ->where('flash_sale_id', $active['id'])
                ->findAll();
        }

        return view('admin/flash-sales/index', [
            'title'    => 'Flash Sales — Solqam Admin',
            'sales'    => $sales,
            'products' => $products,
            'items'    => $items,
            'active'   => $active,
        ]);
    }

    public function store()
    {
        $saleId = (new FlashSaleModel())->insert([
            'title'     => $this->request->getPost('title') ?: 'Flash Sale',
            'starts_at' => $this->normalizeDate($this->request->getPost('starts_at')) ?: date('Y-m-d H:i:s'),
            'ends_at'   => $this->normalizeDate($this->request->getPost('ends_at')) ?: date('Y-m-d H:i:s', strtotime('+1 day')),
            'is_active' => 1,
        ]);

        $productId = (int) $this->request->getPost('product_id');
        $salePrice = (float) $this->request->getPost('sale_price');
        if ($productId && $salePrice > 0) {
            (new FlashSaleItemModel())->insert([
                'flash_sale_id' => $saleId,
                'product_id'    => $productId,
                'sale_price'    => $salePrice,
            ]);
        }

        return redirect()->to('/admin/flash-sales')->with('success', 'Flash sale saved.');
    }

    public function addItem()
    {
        (new FlashSaleItemModel())->insert([
            'flash_sale_id' => (int) $this->request->getPost('flash_sale_id'),
            'product_id'    => (int) $this->request->getPost('product_id'),
            'sale_price'    => (float) $this->request->getPost('sale_price'),
        ]);
        return redirect()->to('/admin/flash-sales')->with('success', 'Deal product added.');
    }

    protected function normalizeDate(?string $value): ?string
    {
        if (!$value) {
            return null;
        }
        $value = str_replace('T', ' ', $value);
        if (strlen($value) === 16) {
            $value .= ':00';
        }
        return $value;
    }
}
