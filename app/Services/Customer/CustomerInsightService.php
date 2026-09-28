<?php

namespace App\Services\Customer;

use App\Models\AddressModel;
use App\Models\OrderItemModel;
use App\Models\OrderModel;
use App\Models\PayLaterApplicationModel;
use App\Models\UserModel;
use App\Services\Wallet\WalletService;
use Config\Database;

class CustomerInsightService
{
    public function dossier(int $customerId, ?int $sellerScopeId = null): ?array
    {
        $user = (new UserModel())->find($customerId);
        if (!$user || ($user['role'] ?? '') !== 'customer') {
            return null;
        }

        helper('marketplace');

        $db = Database::connect();
        $walletService = new WalletService();
        $orderModel = new OrderModel();
        $itemModel = new OrderItemModel();

        $orderBuilder = $orderModel
            ->select('orders.*, payments.payment_method, payments.status as payment_status, payments.amount as payment_amount')
            ->join('payments', 'payments.order_id = orders.id', 'left')
            ->where('orders.user_id', $customerId)
            ->orderBy('orders.id', 'DESC');

        if ($sellerScopeId) {
            $orderIds = $itemModel
                ->select('order_id')
                ->where('seller_id', $sellerScopeId)
                ->groupBy('order_id')
                ->findAll();
            $orderIds = array_column($orderIds, 'order_id');
            if ($orderIds === []) {
                $orders = [];
            } else {
                $orders = $orderBuilder->whereIn('orders.id', $orderIds)->findAll();
            }
        } else {
            $orders = $orderBuilder->findAll();
        }

        $totals = [
            'orders'        => 0,
            'gmv'           => 0.0,
            'wallet_used'   => 0.0,
            'commission'    => 0.0,
            'goods'         => 0.0,
            'net_to_seller' => 0.0,
            'cashback'      => 0.0,
        ];

        foreach ($orders as &$order) {
            $itemsQ = $itemModel->where('order_id', $order['id']);
            if ($sellerScopeId) {
                $itemsQ->where('seller_id', $sellerScopeId);
            }
            $items = $itemsQ->findAll();
            $order['items'] = $items;
            $lineGoods = 0.0;
            $lineComm = 0.0;
            $lineCb = 0.0;
            foreach ($items as $it) {
                $lineGoods += (float) $it['subtotal'];
                $lineComm += (float) $it['commission_amount'];
                $lineCb += item_cashback($it);
            }
            $order['scope_goods'] = $lineGoods;
            $order['scope_commission'] = $lineComm;
            $order['scope_cashback'] = $lineCb;
            $order['scope_net'] = max(0.0, $lineGoods - $lineComm - $lineCb);

            $totals['orders']++;
            $totals['gmv'] += (float) $order['total_amount'];
            $totals['wallet_used'] += (float) $order['wallet_amount_used'];
            $totals['goods'] += $lineGoods;
            $totals['commission'] += $lineComm;
            $totals['net_to_seller'] += $order['scope_net'];
            $order['cashback'] = $lineCb;
            $totals['cashback'] += $lineCb;
        }
        unset($order);

        $addresses = (new AddressModel())->getUserAddresses($customerId);
        $walletBalance = $walletService->getBalance($customerId);
        $ledger = $walletService->getTransactions($customerId, 40);

        if ($sellerScopeId) {
            $allowedOrderIds = array_column($orders, 'id');
            $ledger = array_values(array_filter($ledger, static function ($row) use ($allowedOrderIds) {
                $ref = (int) ($row['reference_id'] ?? 0);
                return $ref <= 0 || in_array($ref, $allowedOrderIds, true);
            }));
        }

        $payLater = [];
        try {
            $plq = (new PayLaterApplicationModel())->where('user_id', $customerId)->orderBy('id', 'DESC');
            $payLater = $plq->findAll(8);
        } catch (\Throwable $e) {
            $payLater = [];
        }

        return [
            'user'           => $user,
            'addresses'      => $addresses,
            'orders'         => $orders,
            'totals'         => $totals,
            'wallet_balance' => $walletBalance,
            'ledger'         => $ledger,
            'pay_later'      => $payLater,
            'seller_scope'   => $sellerScopeId,
        ];
    }

    public function last7DaySeries(?int $sellerId = null): array
    {
        $db = Database::connect();
        $labels = [];
        $gmv = [];
        $comm = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-{$i} days"));
            $labels[] = date('D', strtotime($day));
            $gmv[$day] = 0.0;
            $comm[$day] = 0.0;
        }

        if ($sellerId) {
            $rows = $db->query(
                "SELECT DATE(orders.created_at) AS d,
                        COALESCE(SUM(order_items.subtotal),0) AS gmv,
                        COALESCE(SUM(order_items.commission_amount),0) AS comm
                 FROM order_items
                 JOIN orders ON orders.id = order_items.order_id
                 WHERE order_items.seller_id = ?
                   AND orders.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                 GROUP BY DATE(orders.created_at)",
                [$sellerId]
            )->getResultArray();
        } else {
            $rows = $db->query(
                "SELECT DATE(created_at) AS d,
                        COALESCE(SUM(total_amount),0) AS gmv,
                        COALESCE(SUM(commission_amount),0) AS comm
                 FROM orders
                 WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                 GROUP BY DATE(created_at)"
            )->getResultArray();
        }

        foreach ($rows as $row) {
            $d = $row['d'];
            if (isset($gmv[$d])) {
                $gmv[$d] = (float) $row['gmv'];
                $comm[$d] = (float) $row['comm'];
            }
        }

        return [
            'labels' => $labels,
            'gmv'    => array_values($gmv),
            'comm'   => array_values($comm),
        ];
    }
}
