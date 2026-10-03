<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CategoryModel;
use App\Models\CommissionModel;
use App\Models\OrderModel;
use App\Models\ProductModel;
use App\Models\ReturnRefundModel;
use App\Models\SellerPayoutModel;
use App\Models\SellerProfileModel;
use App\Models\SupportTicketModel;
use App\Models\UserModel;
use App\Services\Analytics\DashboardAnalytics;

class DashboardController extends BaseController
{
    public function index()
    {
        $userModel          = new UserModel();
        $sellerProfileModel = new SellerProfileModel();
        $orderModel         = new OrderModel();
        $productModel       = new ProductModel();
        $db                 = \Config\Database::connect();

        $adminId        = (int) session()->get('user.id');
        $totalCustomers = $userModel->where('role', 'customer')->countAllResults();
        $totalSellers   = $userModel->where('role', 'seller')->countAllResults();
        $pendingSellerCount = (new SellerProfileModel())->where('approval_status', 'pending')->countAllResults();
        $pendingSellers = $sellerProfileModel
            ->select('seller_profiles.*, users.name as owner_name, users.email as owner_email, users.phone as owner_phone')
            ->join('users', 'users.id = seller_profiles.user_id')
            ->where('seller_profiles.approval_status', 'pending')
            ->orderBy('seller_profiles.id', 'DESC')
            ->findAll(10);

        $myProductCount = $productModel->where('seller_id', $adminId)->countAllResults();
        $totalProducts  = $productModel->countAllResults();

        $allOrders             = $orderModel->findAll();
        $totalGmv              = 0.0;
        $totalCommissionEarned = 0.0;
        $totalCashback         = 0.0;
        foreach ($allOrders as $ord) {
            if (in_array($ord['status'] ?? '', ['cancelled', 'returned'], true)) {
                continue;
            }
            $totalGmv              += (float) $ord['total_amount'];
            $totalCommissionEarned += (float) $ord['commission_amount'];
            $totalCashback         += (float) ($ord['cashback_amount'] ?? 0);
        }

        $recentOrders = $orderModel
            ->select('orders.*, users.name as customer_name, users.phone as customer_phone')
            ->join('users', 'users.id = orders.user_id')
            ->orderBy('orders.id', 'DESC')
            ->findAll(10);

        $adminOpenIds = $db->table('order_items')
            ->select('order_id')
            ->where('seller_id', $adminId)
            ->whereNotIn('fulfillment_status', ['delivered', 'cancelled', 'returned', 'undelivered'])
            ->groupBy('order_id')
            ->get()
            ->getResultArray();
        $adminOpenIds = array_column($adminOpenIds, 'order_id');
        $openOrders = [];
        if ($adminOpenIds) {
            $openOrders = $orderModel
                ->select('orders.*, users.name as customer_name, users.phone as customer_phone')
                ->join('users', 'users.id = orders.user_id')
                ->whereIn('orders.id', $adminOpenIds)
                ->orderBy('orders.id', 'DESC')
                ->findAll(10);
            foreach ($openOrders as &$row) {
                $pkg = $db->table('order_items')
                    ->select('fulfillment_status')
                    ->where('order_id', $row['id'])
                    ->where('seller_id', $adminId)
                    ->get()
                    ->getRowArray();
                $row['admin_pkg_status'] = $pkg['fulfillment_status'] ?? $row['status'];
            }
            unset($row);
        }

        $sellerStats = $sellerProfileModel->getSellerStats();

        $statusCounts = [];
        foreach (['placed', 'confirmed', 'shipped', 'delivered', 'cancelled'] as $s) {
            $statusCounts[$s] = $orderModel->where('status', $s)->countAllResults();
        }

        $pendingReturns = (new ReturnRefundModel())
            ->select('returns_refunds.*, orders.order_number, users.name as customer_name')
            ->join('orders', 'orders.id = returns_refunds.order_id', 'left')
            ->join('users', 'users.id = returns_refunds.user_id', 'left')
            ->where('returns_refunds.status', 'requested')
            ->orderBy('returns_refunds.id', 'DESC')
            ->findAll(8);

        $pendingPayouts = (new SellerPayoutModel())
            ->select('seller_payouts.*, seller_profiles.store_name, users.name as seller_name, orders.order_number')
            ->join('seller_profiles', 'seller_profiles.user_id = seller_payouts.seller_id', 'left')
            ->join('users', 'users.id = seller_payouts.seller_id', 'left')
            ->join('orders', 'orders.id = seller_payouts.order_id', 'left')
            ->where('seller_payouts.status !=', 'paid')
            ->orderBy('seller_payouts.id', 'DESC')
            ->findAll(8);

        $openTickets = (new SupportTicketModel())
            ->select('support_tickets.*, users.name as customer_name, users.email as customer_email')
            ->join('users', 'users.id = support_tickets.user_id', 'left')
            ->whereIn('support_tickets.status', ['open', 'replied'])
            ->orderBy('support_tickets.id', 'DESC')
            ->findAll(6);

        $moderationProducts = $productModel
            ->select('products.*, seller_profiles.store_name')
            ->join('seller_profiles', 'seller_profiles.user_id = products.seller_id', 'left')
            ->orderBy('products.id', 'DESC')
            ->findAll(8);

        $commission = (new CommissionModel())->getActiveRule();
        $categoryRates = (new CategoryModel())->where('parent_id', null)->where('is_active', 1)->orderBy('sort_order', 'ASC')->orderBy('name', 'ASC')->findAll(12);
        $commissionService = new \App\Services\Commission\CommissionService();
        foreach ($categoryRates as &$crow) {
            $crow['effective_commission'] = $commissionService->rateForCategory((int) $crow['id']);
        }
        unset($crow);
        $analytics = new DashboardAnalytics();
        $chart = $analytics->daySeries(14);
        $pulse = $analytics->pulse();
        $payMix = $analytics->paymentMix();
        $topSkus = $analytics->topProducts(6);
        $aov = count($allOrders) > 0 ? round($totalGmv / count($allOrders), 0) : 0;

        $walletCashback = 0.0;
        $walletSpend = 0.0;
        try {
            $wrow = $db->query("SELECT
                COALESCE(SUM(CASE WHEN wt.type='credit' AND wt.reference_type='cashback' THEN wt.amount ELSE 0 END),0) AS cashback,
                COALESCE(SUM(CASE WHEN wt.type='debit' AND wt.reference_type='order_payment' THEN wt.amount ELSE 0 END),0) AS spend
                FROM wallet_transactions wt")->getRowArray();
            $walletCashback = (float) ($wrow['cashback'] ?? 0);
            $walletSpend = (float) ($wrow['spend'] ?? 0);
        } catch (\Throwable $e) {
        }

        return view('admin/dashboard', [
            'title'                 => 'Command Center — Solqam Admin',
            'totalCustomers'        => $totalCustomers,
            'totalSellers'          => $totalSellers,
            'pendingSellers'        => $pendingSellers,
            'pendingSellerCount'    => $pendingSellerCount,
            'totalProducts'         => $totalProducts,
            'myProductCount'        => $myProductCount,
            'orderCount'            => count($allOrders),
            'totalGmv'              => $totalGmv,
            'totalCommissionEarned' => $totalCommissionEarned,
            'totalCashback'         => $totalCashback,
            'recentOrders'          => $recentOrders,
            'openOrders'            => $openOrders,
            'sellerStats'           => $sellerStats,
            'statusCounts'          => $statusCounts,
            'pendingReturns'        => $pendingReturns,
            'pendingPayouts'        => $pendingPayouts,
            'openTickets'           => $openTickets,
            'moderationProducts'    => $moderationProducts,
            'commission'            => $commission,
            'categoryRates'         => $categoryRates,
            'chart'                 => $chart,
            'pulse'                 => $pulse,
            'payMix'                => $payMix,
            'topSkus'               => $topSkus,
            'aov'                   => $aov,
            'walletCashback'        => $walletCashback,
            'walletSpend'           => $walletSpend,
        ]);
    }
}
