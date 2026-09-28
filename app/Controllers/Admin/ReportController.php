<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use Config\Database;

class ReportController extends BaseController
{
    public function index()
    {
        $db = Database::connect();

        // 1. Orders by status
        $statusAgg = $db->table('orders')
            ->select('status, COUNT(id) as count, COALESCE(SUM(total_amount), 0) as total_value')
            ->groupBy('status')
            ->get()
            ->getResultArray();

        // 2. Daily sales over the last 14 days
        $salesTimeline = $db->table('orders')
            ->select("DATE(created_at) as order_date, COUNT(id) as order_count, COALESCE(SUM(total_amount), 0) as daily_gmv, COALESCE(SUM(commission_amount), 0) as daily_commission")
            ->groupBy('DATE(created_at)')
            ->orderBy('order_date', 'ASC')
            ->get()
            ->getResultArray();

        // 3. Top Sellers by Sales Volume
        $topSellers = $db->table('order_items')
            ->select('seller_profiles.store_name, users.name as seller_name, COUNT(order_items.id) as units_sold, SUM(order_items.subtotal) as total_revenue, SUM(order_items.commission_amount) as platform_commission')
            ->join('seller_profiles', 'seller_profiles.user_id = order_items.seller_id', 'left')
            ->join('users', 'users.id = order_items.seller_id', 'left')
            ->groupBy('order_items.seller_id')
            ->orderBy('total_revenue', 'DESC')
            ->get()
            ->getResultArray();

        // Overall Totals
        $totals = $db->table('orders')
            ->select("COUNT(id) as total_orders, COALESCE(SUM(total_amount), 0) as total_gmv, COALESCE(SUM(commission_amount), 0) as total_commission, COALESCE(SUM(wallet_amount_used), 0) as total_wallet_used")
            ->get()
            ->getRowArray();

        return view('admin/reports/index', [
            'title'         => 'Executive Analytics & Reports — Solqam Admin',
            'statusAgg'     => $statusAgg,
            'salesTimeline' => $salesTimeline,
            'topSellers'    => $topSellers,
            'totals'        => $totals,
        ]);
    }
}
