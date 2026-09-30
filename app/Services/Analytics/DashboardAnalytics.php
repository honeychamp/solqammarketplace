<?php

namespace App\Services\Analytics;

use Config\Database;

class DashboardAnalytics
{
    public function daySeries(int $days = 14, ?int $sellerId = null): array
    {
        $days = max(7, min(30, $days));
        $db = Database::connect();
        $labels = [];
        $gmv = [];
        $comm = [];
        $orders = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-{$i} days"));
            $labels[] = date('d M', strtotime($day));
            $gmv[$day] = 0.0;
            $comm[$day] = 0.0;
            $orders[$day] = 0;
        }

        try {
            if ($sellerId) {
                $rows = $db->query(
                    "SELECT DATE(orders.created_at) AS d,
                            COALESCE(SUM(order_items.subtotal),0) AS gmv,
                            COALESCE(SUM(order_items.commission_amount),0) AS comm,
                            COUNT(DISTINCT orders.id) AS cnt
                     FROM order_items
                     JOIN orders ON orders.id = order_items.order_id
                     WHERE order_items.seller_id = ?
                       AND orders.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                     GROUP BY DATE(orders.created_at)",
                    [$sellerId, $days - 1]
                )->getResultArray();
            } else {
                $rows = $db->query(
                    "SELECT DATE(created_at) AS d,
                            COALESCE(SUM(total_amount),0) AS gmv,
                            COALESCE(SUM(commission_amount),0) AS comm,
                            COUNT(id) AS cnt
                     FROM orders
                     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                     GROUP BY DATE(created_at)",
                    [$days - 1]
                )->getResultArray();
            }
            foreach ($rows as $row) {
                $d = $row['d'];
                if (isset($gmv[$d])) {
                    $gmv[$d]     = (float) $row['gmv'];
                    $comm[$d]    = (float) $row['comm'];
                    $orders[$d]  = (int) $row['cnt'];
                }
            }
        } catch (\Throwable $e) {
        }

        return [
            'labels' => $labels,
            'gmv'    => array_values($gmv),
            'comm'   => array_values($comm),
            'orders' => array_values($orders),
        ];
    }

    public function pulse(?int $sellerId = null): array
    {
        $db = Database::connect();
        $today = $this->periodTotals('CURDATE()', 'CURDATE() + INTERVAL 1 DAY', $sellerId);
        $yesterday = $this->periodTotals('CURDATE() - INTERVAL 1 DAY', 'CURDATE()', $sellerId);
        $week = $this->periodTotals('CURDATE() - INTERVAL 6 DAY', 'CURDATE() + INTERVAL 1 DAY', $sellerId);
        $lastWeek = $this->periodTotals('CURDATE() - INTERVAL 13 DAY', 'CURDATE() - INTERVAL 6 DAY', $sellerId);

        return [
            'today_gmv'      => $today['gmv'],
            'today_orders'   => $today['orders'],
            'yesterday_gmv'  => $yesterday['gmv'],
            'yesterday_orders' => $yesterday['orders'],
            'week_gmv'       => $week['gmv'],
            'week_orders'    => $week['orders'],
            'last_week_gmv'  => $lastWeek['gmv'],
            'last_week_orders' => $lastWeek['orders'],
        ];
    }

    public function paymentMix(?int $sellerId = null): array
    {
        $db = Database::connect();
        try {
            if ($sellerId) {
                $rows = $db->query(
                    "SELECT COALESCE(payments.payment_method, 'cod') AS method, COUNT(DISTINCT orders.id) AS cnt
                     FROM orders
                     JOIN order_items ON order_items.order_id = orders.id
                     LEFT JOIN payments ON payments.order_id = orders.id
                     WHERE order_items.seller_id = ?
                     GROUP BY method",
                    [$sellerId]
                )->getResultArray();
            } else {
                $rows = $db->query(
                    "SELECT COALESCE(payments.payment_method, 'cod') AS method, COUNT(orders.id) AS cnt
                     FROM orders
                     LEFT JOIN payments ON payments.order_id = orders.id
                     GROUP BY method"
                )->getResultArray();
            }
        } catch (\Throwable $e) {
            return ['labels' => ['COD'], 'values' => [0]];
        }

        $labels = [];
        $values = [];
        foreach ($rows as $row) {
            $labels[] = strtoupper((string) $row['method']);
            $values[] = (int) $row['cnt'];
        }
        if ($labels === []) {
            return ['labels' => ['No payments'], 'values' => [1]];
        }

        return ['labels' => $labels, 'values' => $values];
    }

    public function topProducts(int $limit = 6, ?int $sellerId = null): array
    {
        $db = Database::connect();
        try {
            $sql = "SELECT order_items.product_name, SUM(order_items.quantity) AS units, SUM(order_items.subtotal) AS revenue
                    FROM order_items
                    JOIN orders ON orders.id = order_items.order_id
                    WHERE orders.status NOT IN ('cancelled')";
            $binds = [];
            if ($sellerId) {
                $sql .= ' AND order_items.seller_id = ?';
                $binds[] = $sellerId;
            }
            $sql .= ' GROUP BY order_items.product_name ORDER BY revenue DESC LIMIT ' . (int) $limit;

            return $db->query($sql, $binds)->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function periodTotals(string $fromSql, string $toSql, ?int $sellerId): array
    {
        $db = Database::connect();
        try {
            if ($sellerId) {
                $row = $db->query(
                    "SELECT COALESCE(SUM(order_items.subtotal),0) AS gmv, COUNT(DISTINCT orders.id) AS orders
                     FROM order_items
                     JOIN orders ON orders.id = order_items.order_id
                     WHERE order_items.seller_id = ?
                       AND orders.created_at >= {$fromSql}
                       AND orders.created_at < {$toSql}",
                    [$sellerId]
                )->getRowArray();
            } else {
                $row = $db->query(
                    "SELECT COALESCE(SUM(total_amount),0) AS gmv, COUNT(id) AS orders
                     FROM orders
                     WHERE created_at >= {$fromSql}
                       AND created_at < {$toSql}"
                )->getRowArray();
            }

            return [
                'gmv'    => (float) ($row['gmv'] ?? 0),
                'orders' => (int) ($row['orders'] ?? 0),
            ];
        } catch (\Throwable $e) {
            return ['gmv' => 0.0, 'orders' => 0];
        }
    }
}
