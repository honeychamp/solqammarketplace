<?php

namespace App\Controllers\Seller;

use App\Controllers\BaseController;
use App\Models\ConversationModel;
use App\Models\OrderItemModel;
use App\Models\ProductModel;
use App\Models\ProductQuestionModel;
use App\Models\SellerPayoutModel;
use App\Services\Customer\CustomerInsightService;
use App\Services\Platform\SettingService;
use App\Services\Wallet\WalletService;

class DashboardController extends BaseController
{
    public function index()
    {
        $sellerId = (int) session()->get('user.id');
        $productModel   = new ProductModel();
        $orderItemModel = new OrderItemModel();

        $products = $productModel->where('seller_id', $sellerId)->orderBy('id', 'DESC')->findAll();
        $totalProducts = count($products);
        $lowStock = array_values(array_filter($products, static fn ($p) => (int) $p['stock'] <= 5));

        $sellerItems = $orderItemModel->getSellerOrderItems($sellerId);
        $totalSales = 0.0;
        $totalCommission = 0.0;
        $totalCashback = 0.0;
        $orderIds = [];
        $openOrders = [];
        $buyers = [];
        foreach ($sellerItems as $item) {
            $totalSales += (float) $item['subtotal'];
            $totalCommission += (float) $item['commission_amount'];
            $totalCashback += item_cashback($item);
            $orderIds[$item['order_id']] = true;
            $cid = (int) ($item['customer_id'] ?? 0);
            if ($cid > 0) {
                $buyers[$cid] = true;
            }
            $pkg = $item['fulfillment_status'] ?? $item['order_status'];
            if (! in_array($pkg, ['delivered', 'cancelled', 'returned'], true) && ! isset($openOrders[$item['order_id']])) {
                $openOrders[$item['order_id']] = $item;
            }
        }

        $questions = (new ProductQuestionModel())
            ->select('product_questions.*, products.name as product_name, users.name as asker_name')
            ->join('products', 'products.id = product_questions.product_id')
            ->join('users', 'users.id = product_questions.user_id', 'left')
            ->where('products.seller_id', $sellerId)
            ->where("(product_questions.answer IS NULL OR product_questions.answer = '')", null, false)
            ->orderBy('product_questions.id', 'DESC')
            ->findAll(8);

        $payouts = (new SellerPayoutModel())
            ->select('seller_payouts.*, orders.order_number')
            ->join('orders', 'orders.id = seller_payouts.order_id', 'left')
            ->where('seller_payouts.seller_id', $sellerId)
            ->orderBy('seller_payouts.id', 'DESC')
            ->findAll(8);

        $pendingPayout = 0.0;
        $paidPayout = 0.0;
        foreach ($payouts as $p) {
            if (($p['status'] ?? '') === 'paid') {
                $paidPayout += (float) $p['amount'];
            } else {
                $pendingPayout += (float) $p['amount'];
            }
        }

        $chats = (new ConversationModel())->forUser($sellerId, 'seller');
        $chats = array_slice($chats, 0, 6);
        $chart = (new CustomerInsightService())->last7DaySeries($sellerId);
        $sellerWallet = (new WalletService())->getBalance($sellerId);
        $confirmH = SettingService::int('sla_confirm_hours', 24);
        $shipH = SettingService::int('sla_ship_hours', 72);
        $now = time();
        $slaBreaches = [];
        foreach ($openOrders as $item) {
            $created = strtotime((string) ($item['order_date'] ?? $item['created_at'] ?? 'now')) ?: $now;
            $hours = ($now - $created) / 3600;
            $status = $item['fulfillment_status'] ?? $item['order_status'] ?? 'placed';
            if ($status === 'placed' && $hours > $confirmH) {
                $slaBreaches[] = $item + ['_sla' => 'Confirm overdue'];
            } elseif (in_array($status, ['placed', 'confirmed'], true) && $hours > $shipH) {
                $slaBreaches[] = $item + ['_sla' => 'Ship overdue'];
            }
        }

        return view('seller/dashboard', [
            'title'           => 'Seller Command Center — Solqam',
            'totalProducts'   => $totalProducts,
            'totalOrders'     => count($orderIds),
            'totalSales'      => $totalSales,
            'totalCommission' => $totalCommission,
            'totalCashback'   => $totalCashback,
            'netEarnings'     => max(0.0, $totalSales - $totalCommission - $totalCashback),
            'buyerCount'      => count($buyers),
            'lowStockCount'   => count($lowStock),
            'openOrders'      => array_values($openOrders),
            'recentOrders'    => array_slice($sellerItems, 0, 8),
            'questions'       => $questions,
            'payouts'         => $payouts,
            'pendingPayout'   => $pendingPayout,
            'paidPayout'      => $paidPayout,
            'chats'           => $chats,
            'products'        => array_slice($products, 0, 12),
            'lowStock'        => array_slice($lowStock, 0, 8),
            'chart'           => $chart,
            'sellerWallet'    => $sellerWallet,
            'slaBreaches'     => $slaBreaches,
            'slaConfirmHours' => $confirmH,
            'slaShipHours'    => $shipH,
        ]);
    }

    public function performance()
    {
        $sellerId = (int) session()->get('user.id');
        $orderItemModel = new OrderItemModel();
        $sellerItems = $orderItemModel->getSellerOrderItems($sellerId);

        $productSales = [];
        foreach ($sellerItems as $item) {
            $name = $item['product_name'];
            if (!isset($productSales[$name])) {
                $productSales[$name] = ['units' => 0, 'revenue' => 0.0];
            }
            $productSales[$name]['units'] += (int) $item['quantity'];
            $productSales[$name]['revenue'] += (float) $item['subtotal'];
        }

        uasort($productSales, fn($a, $b) => $b['revenue'] <=> $a['revenue']);

        return view('seller/performance', [
            'title'        => 'Seller Performance — Solqam Market Place',
            'productSales' => $productSales,
        ]);
    }
}
