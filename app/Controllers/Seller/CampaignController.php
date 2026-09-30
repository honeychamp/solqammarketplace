<?php

namespace App\Controllers\Seller;

use App\Controllers\BaseController;
use App\Models\FlashSaleItemModel;
use App\Models\FlashSaleModel;
use App\Models\ProductModel;

class CampaignController extends BaseController
{
    public function index()
    {
        $sellerId = (int) session()->get('user.id');
        $campaigns = (new FlashSaleModel())->getOpenForSellerJoin();
        $products = (new ProductModel())
            ->where('seller_id', $sellerId)
            ->where('status', 'active')
            ->orderBy('name', 'ASC')
            ->findAll();
        $mine = (new FlashSaleItemModel())
            ->select('flash_sale_items.*, flash_sales.title, flash_sales.campaign_type, flash_sales.starts_at, flash_sales.ends_at, products.name as product_name, products.price as list_price')
            ->join('flash_sales', 'flash_sales.id = flash_sale_items.flash_sale_id')
            ->join('products', 'products.id = flash_sale_items.product_id')
            ->where('flash_sale_items.seller_id', $sellerId)
            ->orderBy('flash_sale_items.id', 'DESC')
            ->findAll(40);

        return view('seller/campaigns/index', [
            'title'      => 'Join campaigns — Seller Hub',
            'campaigns'  => $campaigns,
            'products'   => $products,
            'mine'       => $mine,
        ]);
    }

    public function join()
    {
        $sellerId = (int) session()->get('user.id');
        $saleId = (int) $this->request->getPost('flash_sale_id');
        $productId = (int) $this->request->getPost('product_id');
        $salePrice = (float) $this->request->getPost('sale_price');
        $sale = (new FlashSaleModel())->find($saleId);
        $product = (new ProductModel())->find($productId);

        if (! $sale || (int) ($sale['seller_join'] ?? 0) !== 1 || (int) ($sale['is_active'] ?? 0) !== 1) {
            return redirect()->back()->with('error', 'This campaign is not open for seller join.');
        }
        if (strtotime((string) $sale['ends_at']) < time()) {
            return redirect()->back()->with('error', 'Campaign has ended.');
        }
        if (! $product || (int) $product['seller_id'] !== $sellerId || ($product['status'] ?? '') !== 'active') {
            return redirect()->back()->with('error', 'Pick one of your live products.');
        }
        if ($salePrice <= 0 || $salePrice >= (float) $product['price']) {
            return redirect()->back()->with('error', 'Campaign price must be lower than your listed price.');
        }

        $items = new FlashSaleItemModel();
        $existing = $items->where('flash_sale_id', $saleId)->where('product_id', $productId)->first();
        if ($existing) {
            if (($existing['status'] ?? '') === 'approved') {
                return redirect()->back()->with('info', 'This SKU is already live on the campaign.');
            }
            $items->update($existing['id'], [
                'sale_price' => $salePrice,
                'status'     => 'pending',
                'source'     => 'seller',
                'seller_id'  => $sellerId,
            ]);
        } else {
            $items->insert([
                'flash_sale_id' => $saleId,
                'product_id'    => $productId,
                'seller_id'     => $sellerId,
                'sale_price'    => $salePrice,
                'status'        => 'pending',
                'source'        => 'seller',
            ]);
        }

        return redirect()->to('/seller/campaigns')->with('success', 'Submitted. Live after admin approval.');
    }
}
