<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ProductModel;
use App\Services\Catalog\InventoryService;

class InventoryController extends BaseController
{
    public function index()
    {
        $adminId = (int) session()->get('user.id');
        $mine    = (string) $this->request->getGet('mine') === '1';
        $pack    = (new InventoryService())->build(null, [
            'filter'   => (string) $this->request->getGet('filter'),
            'q'        => (string) $this->request->getGet('q'),
            'mine'     => $mine,
            'admin_id' => $adminId,
        ]);

        return view('admin/inventory', [
            'title'       => 'Inventory — Solqam Admin',
            'rows'        => $pack['rows'],
            'summary'     => $pack['summary'],
            'filter'      => $pack['filter'],
            'q'           => $pack['q'],
            'showStore'   => true,
            'mine'        => $mine,
            'listUrl'     => site_url('admin/inventory'),
            'editBase'    => site_url('admin/products/edit'),
            'stockUrl'    => site_url('admin/inventory/stock'),
            'oosUrl'      => site_url('admin/products/out-of-stock'),
            'productsUrl' => site_url('admin/products'),
        ]);
    }

    public function updateStock($id)
    {
        $product = (new ProductModel())->find((int) $id);
        if (! $product) {
            return redirect()->to('/admin/inventory')->with('error', 'Product not found.');
        }
        $stock = max(0, (int) $this->request->getPost('stock'));
        (new ProductModel())->update((int) $id, ['stock' => $stock]);

        return redirect()->to($this->backUrl('/admin/inventory'))->with('success', 'Stock updated.');
    }

    protected function backUrl(string $fallback): string
    {
        $back = (string) $this->request->getPost('back');
        if ($back !== '' && str_starts_with($back, '/admin/inventory')) {
            return $back;
        }

        return $fallback;
    }
}
