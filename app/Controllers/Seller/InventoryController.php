<?php

namespace App\Controllers\Seller;

use App\Controllers\BaseController;
use App\Models\ProductModel;
use App\Services\Catalog\InventoryService;

class InventoryController extends BaseController
{
    public function index()
    {
        $sellerId = (int) session()->get('user.id');
        $pack     = (new InventoryService())->build($sellerId, [
            'filter' => (string) $this->request->getGet('filter'),
            'q'      => (string) $this->request->getGet('q'),
        ]);

        return view('seller/inventory', [
            'title'      => 'Inventory — Solqam Seller Hub',
            'rows'       => $pack['rows'],
            'summary'    => $pack['summary'],
            'filter'     => $pack['filter'],
            'q'          => $pack['q'],
            'showStore'  => false,
            'mine'       => false,
            'listUrl'    => site_url('seller/inventory'),
            'editBase'   => site_url('seller/products/edit'),
            'stockUrl'   => site_url('seller/inventory/stock'),
            'oosUrl'     => site_url('seller/products/out-of-stock'),
            'productsUrl'=> site_url('seller/products'),
        ]);
    }

    public function updateStock($id)
    {
        $sellerId = (int) session()->get('user.id');
        $product  = (new ProductModel())->where('id', (int) $id)->where('seller_id', $sellerId)->first();
        if (! $product) {
            return redirect()->to('/seller/inventory')->with('error', 'Product not found.');
        }
        $stock = max(0, (int) $this->request->getPost('stock'));
        (new ProductModel())->update((int) $id, ['stock' => $stock]);

        return redirect()->to($this->backUrl('/seller/inventory'))->with('success', 'Stock updated.');
    }

    protected function backUrl(string $fallback): string
    {
        $back = (string) $this->request->getPost('back');
        if ($back !== '' && str_starts_with($back, '/seller/inventory')) {
            return $back;
        }

        return $fallback;
    }
}
