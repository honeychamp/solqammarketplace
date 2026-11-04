<?php

namespace App\Services\Export;

use Config\Database;

class OrderExportService
{
    public const GROUPS = [
        ['label' => 'A. Order', 'span' => 6],
        ['label' => 'B. Store', 'span' => 3],
        ['label' => 'C. Customer', 'span' => 7],
        ['label' => 'D. Address', 'span' => 7],
        ['label' => 'E. Product', 'span' => 10],
        ['label' => 'F. Packing', 'span' => 6],
        ['label' => 'G. Shipping', 'span' => 7],
        ['label' => 'H. Money', 'span' => 10],
        ['label' => 'I. Instructions', 'span' => 3],
        ['label' => 'J. Status', 'span' => 4],
    ];

    public const HEADERS = [
        'Order #', 'Order Date', 'Order Time (PKT)', 'Order Day', 'Order Source', 'Order Type',
        'Store Name', 'Store ID', 'Seller Contact',
        'Customer Name', 'Phone (Primary)', 'Phone (Alternate / WhatsApp)', 'Email', 'Customer Type', 'Total Past Orders', 'Login Method',
        'Full Address', 'Area / Locality', 'City', 'Province', 'Postal Code', 'Nearest Landmark', 'Address Status',
        'Product Name', 'SKU', 'Variant', 'Category', 'Supplier / Source', 'Unit Price (Rs)', 'Quantity (Units)', 'Line Total (Rs)', 'Total Products in Order', 'Total Pieces in Order',
        'Packing Type', 'Packing Cost (Rs)', 'Gift Wrap', 'Gift Wrap Charge (Rs)', 'Gift Message', 'Package Weight (kg)',
        'Shipping Method', 'Courier', 'Tracking Number', 'Shipping Charged to Customer (Rs)', 'Actual Shipping Cost (Rs)', 'Dispatch Date', 'Delivered Date',
        'Subtotal (Rs)', 'Discount / Coupon Code', 'Discount Amount (Rs)', 'Cashback (Rs)', 'Commission (Rs)', 'Grand Total (Rs)', 'Payment Method', 'Payment Status', 'Product Cost (Rs)', 'Net Profit (Rs)',
        'Customer Note / Instruction', 'Preferred Delivery Time', 'Internal Admin Note',
        'Order Status', 'Confirmation Status', 'Cancel / Return Reason', 'Last Updated',
    ];

    /** Money + packing/shipping cost indexes (0-based) that print only on first line of an order. */
    protected const FIRST_ROW_ONLY = [31, 32, 34, 36, 38, 43, 44, 46, 47, 48, 49, 50, 51, 54, 55];

    public function filtersFromRequest($request, ?int $sellerId = null): array
    {
        $preset = (string) $request->getGet('preset');
        $from   = trim((string) $request->getGet('from'));
        $to     = trim((string) $request->getGet('to'));
        $tz     = new \DateTimeZone('Asia/Karachi');
        $today  = new \DateTimeImmutable('now', $tz);

        if ($preset === 'today') {
            $from = $today->format('Y-m-d');
            $to   = $today->format('Y-m-d');
        } elseif ($preset === 'yesterday') {
            $d = $today->modify('-1 day');
            $from = $d->format('Y-m-d');
            $to   = $d->format('Y-m-d');
        } elseif ($preset === '7d') {
            $from = $today->modify('-6 days')->format('Y-m-d');
            $to   = $today->format('Y-m-d');
        } elseif ($preset === 'month') {
            $from = $today->modify('first day of this month')->format('Y-m-d');
            $to   = $today->format('Y-m-d');
        }

        return [
            'from'            => $from,
            'to'              => $to,
            'status'          => trim((string) $request->getGet('status')),
            'city'            => trim((string) $request->getGet('city')),
            'payment_method'  => trim((string) $request->getGet('payment_method')),
            'store'           => $sellerId ?: (int) $request->getGet('store'),
            'hide_test'       => $request->getGet('hide_test') === '0' ? 0 : 1,
            'format'          => strtolower((string) $request->getGet('format')) === 'csv' ? 'csv' : 'xlsx',
        ];
    }

    /**
     * @return array{rows: list<list<array{v:mixed,t:string}>>, summary: list<list<array{v:mixed,t:string}>>, pending: list<list<array{v:mixed,t:string}>>}
     */
    public function build(array $filters, ?int $sellerOnlyId = null): array
    {
        $lines = $this->loadLines($filters, $sellerOnlyId);
        $orderMeta = [];
        foreach ($lines as $line) {
            $oid = (int) $line['order_id'];
            if (! isset($orderMeta[$oid])) {
                $orderMeta[$oid] = ['products' => 0, 'pieces' => 0, 'seen' => []];
            }
            $pid = (int) ($line['product_id'] ?? 0) . ':' . (int) ($line['variant_id'] ?? 0);
            if (! isset($orderMeta[$oid]['seen'][$pid])) {
                $orderMeta[$oid]['seen'][$pid] = true;
                $orderMeta[$oid]['products']++;
            }
            $orderMeta[$oid]['pieces'] += (int) ($line['quantity'] ?? 0);
        }

        $phoneCounts = $this->phoneOrderCounts($lines);

        $detail = [];
        $pending = [];
        $prevOrder = 0;
        foreach ($lines as $line) {
            $oid = (int) $line['order_id'];
            $first = $oid !== $prevOrder;
            $prevOrder = $oid;
            $row = $this->mapRow($line, $first, $orderMeta[$oid], $phoneCounts);
            $detail[] = $row;
            $st = strtolower((string) ($line['order_status'] ?? $line['status'] ?? ''));
            $ful = strtolower((string) ($line['fulfillment_status'] ?? ''));
            if ($st === 'confirmed' && ! in_array($ful, ['shipped', 'delivered', 'cancelled'], true)) {
                $pending[] = $row;
            }
        }

        return [
            'rows'     => $detail,
            'summary'  => $this->summaryRows($detail, $lines),
            'pending'  => $pending,
        ];
    }

    public function download(array $filters, ?int $sellerOnlyId, string $filenameBase)
    {
        try {
            \App\Services\Platform\SchemaHeal::ensureExportColumns();
        } catch (\Throwable $e) {
            log_message('error', 'Order export schema: ' . $e->getMessage());
        }

        $pack = $this->build($filters, $sellerOnlyId);
        $format = $filters['format'] ?? 'xlsx';
        if ($format === 'csv') {
            return $this->csvResponse($pack['rows'], $filenameBase . '.csv');
        }

        return $this->excelDownload($pack, $filenameBase);
    }

    public function excelDownload(array $pack, string $filenameBase)
    {
        return (new \App\Controllers\Admin\OrderController())->sendOrderSpreadsheet(
            $pack,
            $filenameBase,
            self::HEADERS,
            self::GROUPS
        );
    }


    /**
     * @param list<list<array{v:mixed,t:string}>> $rows
     */
    protected function csvResponse(array $rows, string $filename)
    {
        $fh = fopen('php://temp', 'r+');
        fprintf($fh, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($fh, self::HEADERS);
        foreach ($rows as $row) {
            $out = [];
            foreach ($row as $cell) {
                $out[] = $cell['v'] ?? '';
            }
            fputcsv($fh, $out);
        }
        rewind($fh);
        $csv = stream_get_contents($fh) ?: '';
        fclose($fh);

        return service('response')
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($csv);
    }

    protected function loadLines(array $filters, ?int $sellerOnlyId): array
    {
        $db = Database::connect();
        $builder = $db->table('order_items')
            ->select('order_items.*, orders.order_number, orders.user_id, orders.status as order_status, orders.created_at as order_created,
                orders.updated_at as order_updated, orders.total_amount, orders.discount_amount, orders.shipping_amount,
                orders.coupon_code, orders.coupon_discount, orders.commission_amount as order_commission, orders.cashback_amount as order_cashback,
                orders.final_payable, orders.notes, orders.tracking_number, orders.courier, orders.delivery_date,
                users.name as customer_name, users.email as customer_email, users.phone as user_phone,
                addresses.recipient_name, addresses.phone as recipient_phone, addresses.street_address, addresses.city, addresses.province, addresses.postal_code,
                payments.payment_method, payments.status as payment_status,
                seller_profiles.store_name,
                su.name as seller_name, su.phone as seller_phone,
                products.sku as product_sku, products.brand, products.category_id,
                categories.name as category_name')
            ->join('orders', 'orders.id = order_items.order_id')
            ->join('users', 'users.id = orders.user_id', 'left')
            ->join('addresses', 'addresses.id = orders.address_id', 'left')
            ->join('payments', 'payments.order_id = orders.id', 'left')
            ->join('seller_profiles', 'seller_profiles.user_id = order_items.seller_id', 'left')
            ->join('users as su', 'su.id = order_items.seller_id', 'left')
            ->join('products', 'products.id = order_items.product_id', 'left')
            ->join('categories', 'categories.id = products.category_id', 'left');

        $sellerId = $sellerOnlyId ?: (int) ($filters['store'] ?? 0);
        if ($sellerId > 0) {
            $builder->where('order_items.seller_id', $sellerId);
        }
        if (($filters['status'] ?? '') !== '') {
            $builder->where('orders.status', $filters['status']);
        }
        if (($filters['city'] ?? '') !== '') {
            $builder->like('addresses.city', $filters['city']);
        }
        if (($filters['payment_method'] ?? '') !== '') {
            $builder->where('payments.payment_method', $filters['payment_method']);
        }
        if (($filters['from'] ?? '') !== '') {
            $builder->where('orders.created_at >=', $filters['from'] . ' 00:00:00');
        }
        if (($filters['to'] ?? '') !== '') {
            $builder->where('orders.created_at <=', $filters['to'] . ' 23:59:59');
        }
        if (! empty($filters['hide_test'])) {
            $builder->groupStart()
                ->where('orders.notes', null)
                ->orWhere('orders.notes', '')
                ->orNotLike('orders.notes', 'TEST')
                ->groupEnd();
        }

        $builder->orderBy('orders.created_at', 'DESC')->orderBy('order_items.id', 'ASC');

        try {
            $rows = $builder->get()->getResultArray();
        } catch (\Throwable $e) {
            log_message('error', 'Order export query: ' . $e->getMessage());

            return [];
        }

        $this->attachOptional($rows);

        return $rows;
    }

    protected function attachOptional(array &$rows): void
    {
        if ($rows === []) {
            return;
        }
        $db = Database::connect();
        $orderIds = array_unique(array_map(static fn ($r) => (int) $r['order_id'], $rows));
        $extraOrders = [];
        try {
            $extraOrders = $db->table('orders')->whereIn('id', $orderIds)->get()->getResultArray();
            $extraOrders = array_column($extraOrders, null, 'id');
        } catch (\Throwable $e) {
        }
        $addrIds = [];
        foreach ($extraOrders as $o) {
            if (! empty($o['address_id'])) {
                $addrIds[] = (int) $o['address_id'];
            }
        }
        $addrs = [];
        if ($addrIds) {
            try {
                $addrs = array_column($db->table('addresses')->whereIn('id', $addrIds)->get()->getResultArray(), null, 'id');
            } catch (\Throwable $e) {
            }
        }
        $productIds = array_unique(array_filter(array_map(static fn ($r) => (int) ($r['product_id'] ?? 0), $rows)));
        $products = [];
        if ($productIds) {
            try {
                $products = array_column($db->table('products')->whereIn('id', $productIds)->get()->getResultArray(), null, 'id');
            } catch (\Throwable $e) {
            }
        }
        $ships = [];
        try {
            foreach ($db->table('shipments')->whereIn('order_id', $orderIds)->get()->getResultArray() as $s) {
                $key = ((int) $s['order_id']) . ':' . ((int) ($s['seller_id'] ?? 0));
                $ships[$key] = $s;
            }
        } catch (\Throwable $e) {
        }

        foreach ($rows as &$row) {
            $oid = (int) $row['order_id'];
            $ord = $extraOrders[$oid] ?? [];
            $addr = $addrs[(int) ($ord['address_id'] ?? 0)] ?? [];
            $prod = $products[(int) ($row['product_id'] ?? 0)] ?? [];
            $ship = $ships[$oid . ':' . (int) ($row['seller_id'] ?? 0)] ?? [];
            $row['_ord']  = $ord;
            $row['_addr'] = $addr;
            $row['_prod'] = $prod;
            $row['_ship'] = $ship;
        }
        unset($row);
    }

    /**
     * @param array<string,int> $phoneCounts
     * @return list<array{v:mixed,t:string}>
     */
    protected function mapRow(array $line, bool $first, array $meta, array $phoneCounts): array
    {
        $tz = new \DateTimeZone('Asia/Karachi');
        $created = $this->pkt($line['order_created'] ?? '', $tz);
        $updated = $this->pkt($line['order_updated'] ?? '', $tz);
        $ord  = $line['_ord'] ?? [];
        $addr = $line['_addr'] ?? [];
        $prod = $line['_prod'] ?? [];
        $ship = $line['_ship'] ?? [];

        $phone = $this->digitsPhone((string) ($line['recipient_phone'] ?? $line['user_phone'] ?? ''));
        $name  = trim((string) ($line['recipient_name'] ?? $line['customer_name'] ?? ''));
        $street = trim((string) ($line['street_address'] ?? ''));
        $city = trim((string) ($line['city'] ?? ''));
        $email = (string) ($line['customer_email'] ?? '');
        $login = $this->loginMethod($line, $email);
        $past  = (int) ($phoneCounts[$phone] ?? 1);
        $qty   = (int) ($line['quantity'] ?? 0);
        $unit  = (float) ($line['price'] ?? 0);
        $lineTotal = (float) ($line['subtotal'] ?? ($unit * $qty));
        $giftYes = (int) ($line['gift_wrap'] ?? 0) === 1;
        $giftFee = (float) ($line['gift_wrap_fee'] ?? 0);
        $orderGift = (float) ($line['gift_wrap_amount'] ?? $ord['gift_wrap_amount'] ?? 0);
        $packCost = (float) ($prod['packing_cost'] ?? $ord['packing_cost'] ?? 0);
        $shipCharged = (float) ($line['shipping_amount'] ?? 0);
        $shipActual = (float) ($ord['actual_shipping_cost'] ?? 0);
        $subtotal = (float) ($line['total_amount'] ?? 0) - $shipCharged + 0;
        $goods = (float) ($line['total_amount'] ?? 0) - $shipCharged - $orderGift;
        if ($goods < 0) {
            $goods = (float) ($line['total_amount'] ?? 0);
        }
        $discount = (float) ($line['coupon_discount'] ?? $line['discount_amount'] ?? 0);
        $cashback = (float) ($line['order_cashback'] ?? 0);
        $commission = (float) ($line['order_commission'] ?? 0);
        $grand = (float) ($line['final_payable'] ?? $line['total_amount'] ?? 0);
        $unitCost = (float) ($prod['product_cost'] ?? 0);
        $lineCost = $unitCost * $qty;
        $orderCost = $first ? $lineCost : 0;
        $net = $first ? round($grand - $orderCost - $packCost - $shipActual - $commission - $cashback, 2) : '';

        $courier = (string) ($ship['courier'] ?? $line['courier'] ?? '');
        $track = (string) ($ship['tracking_number'] ?? $line['tracking_number'] ?? '');
        $status = (string) ($line['order_status'] ?? '');
        $area = (string) ($addr['area'] ?? '');
        $landmark = (string) ($addr['landmark'] ?? '');
        $alt = $this->digitsPhone((string) ($addr['alternate_phone'] ?? ''));
        $source = (string) ($ord['order_source'] ?? 'Website');
        $type = (string) ($ord['order_type'] ?? 'Normal');
        if ($type === '' && stripos((string) ($line['notes'] ?? ''), 'test') !== false) {
            $type = 'Test';
        }
        $storeName = (string) ($line['store_name'] ?? $line['seller_name'] ?? 'Store');
        $adminHint = stripos($storeName, 'admin') !== false ? $storeName : $storeName;
        $sid = (int) ($line['seller_id'] ?? 0);

        $s = static fn ($v) => ['v' => $v, 't' => 's'];
        $n = static fn ($v) => ['v' => $v, 't' => 'n'];
        $blankN = ['v' => '', 't' => 'n'];

        $dispatch = $this->ymd($ship['dispatched_at'] ?? $ord['dispatch_date'] ?? '');
        $delivered = $this->ymd($line['delivery_date'] ?? $ord['delivery_date'] ?? '');

        $row = [
            $s((string) ($line['order_number'] ?? '')),
            $s($created['date']),
            $s($created['time']),
            $s($created['day']),
            $s($source !== '' ? $source : 'Website'),
            $s($type !== '' ? $type : 'Normal'),
            $s($adminHint),
            $s($sid > 0 ? 'ST-' . str_pad((string) $sid, 3, '0', STR_PAD_LEFT) : ''),
            $s($this->digitsPhone((string) ($line['seller_phone'] ?? ''))),
            $s($name),
            $s($phone),
            $s($alt),
            $s($this->publicEmail($email)),
            $s($past > 1 ? 'Returning' : 'New'),
            $n($past),
            $s($login),
            $s($street),
            $s($area),
            $s($city),
            $s((string) ($line['province'] ?? '')),
            $s((string) ($line['postal_code'] ?? '')),
            $s($landmark),
            $s(strlen($street) >= 12 && $city !== '' ? 'Complete' : 'Incomplete'),
            $s((string) ($line['product_name'] ?? '')),
            $s((string) ($line['product_sku'] ?? $prod['sku'] ?? '')),
            $s((string) ($line['variant_label'] ?? '')),
            $s((string) ($line['category_name'] ?? '')),
            $s((string) ($prod['supplier'] ?? '')),
            $n($unit),
            $n($qty),
            $n($lineTotal),
            $first ? $n($meta['products']) : $blankN,
            $first ? $n($meta['pieces']) : $blankN,
            $s((string) ($prod['packing_type'] ?? $ord['packing_type'] ?? '')),
            $first ? $n($packCost) : $blankN,
            $s($giftYes ? 'Yes' : 'No'),
            $first ? $n($orderGift > 0 ? $orderGift : ($giftYes ? $giftFee : 0)) : $blankN,
            $s((string) ($line['gift_message'] ?? '')),
            $first ? $n((float) ($prod['weight'] ?? $ord['package_weight'] ?? 0) ?: '') : $blankN,
            $s((string) ($ord['shipping_method'] ?? 'Standard')),
            $s($courier),
            $s($track),
            $first ? $n($shipCharged) : $blankN,
            $first ? $n($shipActual ?: '') : $blankN,
            $s($dispatch),
            $s($delivered),
            $first ? $n($goods) : $blankN,
            $s((string) ($line['coupon_code'] ?? '')),
            $first ? $n($discount) : $blankN,
            $first ? $n($cashback) : $blankN,
            $first ? $n($commission) : $blankN,
            $first ? $n($grand) : $blankN,
            $s($this->payLabel((string) ($line['payment_method'] ?? 'cod'))),
            $s(ucfirst((string) ($line['payment_status'] ?? 'pending'))),
            $first ? $n($orderCost ?: '') : $blankN,
            $first ? (is_numeric($net) ? $n($net) : $blankN) : $blankN,
            $s((string) ($line['notes'] ?? '')),
            $s((string) ($ord['preferred_delivery_time'] ?? '')),
            $s((string) ($ord['internal_note'] ?? '')),
            $s(ucfirst($status)),
            $s((string) ($ord['confirmation_status'] ?? '')),
            $s((string) ($ord['cancel_reason'] ?? '')),
            $s($updated['date'] !== '' ? $updated['date'] . ' ' . $updated['time'] : ''),
        ];

        return $row;
    }

    /**
     * @param list<list<array{v:mixed,t:string}>> $detail
     */
    protected function summaryRows(array $detail, array $lines): array
    {
        $s = static fn ($v) => ['v' => $v, 't' => 's'];
        $n = static fn ($v) => ['v' => $v, 't' => 'n'];
        $orders = [];
        $pieces = 0;
        $sales = 0.0;
        $pack = 0.0;
        $ship = 0.0;
        $giftN = 0;
        $profit = 0.0;
        $byCity = [];
        $byStore = [];
        $byStatus = [];
        $prev = '';
        foreach ($detail as $row) {
            $num = (string) ($row[0]['v'] ?? '');
            $first = $num !== $prev;
            $prev = $num;
            if ($first) {
                $orders[$num] = true;
                $sales += (float) ($row[51]['v'] ?? 0);
                $pack += (float) ($row[34]['v'] ?? 0);
                $ship += (float) ($row[44]['v'] ?? 0);
                $profit += (float) ($row[55]['v'] ?? 0);
                $city = (string) ($row[18]['v'] ?? '');
                $store = (string) ($row[6]['v'] ?? '');
                $st = (string) ($row[59]['v'] ?? '');
                $byCity[$city] = ($byCity[$city] ?? 0) + 1;
                $byStore[$store] = ($byStore[$store] ?? 0) + 1;
                $byStatus[$st] = ($byStatus[$st] ?? 0) + 1;
                if (strcasecmp((string) ($row[35]['v'] ?? ''), 'Yes') === 0) {
                    $giftN++;
                }
            }
            $pieces += (int) ($row[29]['v'] ?? 0);
        }

        $out = [
            [$s('Total orders'), $n(count($orders))],
            [$s('Total pieces'), $n($pieces)],
            [$s('Total sales (Rs)'), $n(round($sales, 2))],
            [$s('Total packing cost (Rs)'), $n(round($pack, 2))],
            [$s('Total shipping cost (Rs)'), $n(round($ship, 2))],
            [$s('Gift-wrapped orders'), $n($giftN)],
            [$s('Net profit (Rs)'), $n(round($profit, 2))],
            [$s(''), $s('')],
            [$s('Orders by city'), $s('')],
        ];
        arsort($byCity);
        foreach ($byCity as $k => $v) {
            $out[] = [$s($k !== '' ? $k : '(blank)'), $n($v)];
        }
        $out[] = [$s(''), $s('')];
        $out[] = [$s('Orders by store'), $s('')];
        arsort($byStore);
        foreach ($byStore as $k => $v) {
            $out[] = [$s($k !== '' ? $k : '(blank)'), $n($v)];
        }
        $out[] = [$s(''), $s('')];
        $out[] = [$s('Orders by status'), $s('')];
        foreach ($byStatus as $k => $v) {
            $out[] = [$s($k !== '' ? $k : '(blank)'), $n($v)];
        }

        return $out;
    }

    protected function phoneOrderCounts(array $lines): array
    {
        $phones = [];
        foreach ($lines as $line) {
            $p = $this->digitsPhone((string) ($line['recipient_phone'] ?? $line['user_phone'] ?? ''));
            if ($p !== '') {
                $phones[$p] = true;
            }
        }
        $list = array_keys($phones);
        if ($list === []) {
            return [];
        }
        $counts = [];
        try {
            $rows = Database::connect()->table('orders')
                ->select('addresses.phone, COUNT(DISTINCT orders.id) as cnt')
                ->join('addresses', 'addresses.id = orders.address_id', 'left')
                ->whereIn('addresses.phone', $list)
                ->groupBy('addresses.phone')
                ->get()
                ->getResultArray();
            foreach ($rows as $r) {
                $counts[$this->digitsPhone((string) $r['phone'])] = (int) $r['cnt'];
            }
        } catch (\Throwable $e) {
        }

        return $counts;
    }

    protected function pkt(string $raw, \DateTimeZone $tz): array
    {
        $raw = trim($raw);
        if ($raw === '' || str_starts_with($raw, '0000-00-00')) {
            return ['date' => '', 'time' => '', 'day' => ''];
        }
        try {
            $dt = new \DateTimeImmutable($raw);
            $dt = $dt->setTimezone($tz);

            return [
                'date' => $dt->format('d-M-Y'),
                'time' => $dt->format('h:i A'),
                'day'  => $dt->format('l'),
            ];
        } catch (\Throwable $e) {
            return ['date' => '', 'time' => '', 'day' => ''];
        }
    }

    protected function ymd($raw): string
    {
        $raw = trim((string) $raw);
        if ($raw === '' || str_starts_with($raw, '0000-00-00')) {
            return '';
        }
        try {
            return (new \DateTimeImmutable($raw))->setTimezone(new \DateTimeZone('Asia/Karachi'))->format('d-M-Y');
        } catch (\Throwable $e) {
            return '';
        }
    }

    protected function digitsPhone(string $phone): string
    {
        $d = preg_replace('/\D+/', '', $phone) ?? '';
        if (strlen($d) === 10) {
            return '0' . $d;
        }
        if (str_starts_with($d, '92') && strlen($d) === 12) {
            return '0' . substr($d, 2);
        }

        return $d;
    }

    protected function loginMethod(array $line, string $email): string
    {
        if (str_contains(strtolower($email), '@guest.solqam.local')) {
            return 'Guest';
        }
        $p = strtolower((string) ($line['oauth_provider'] ?? ''));
        if ($p === 'google' || ! empty($line['google_id'])) {
            return 'Google';
        }
        if ($p === 'facebook' || ! empty($line['facebook_id'])) {
            return 'Facebook';
        }

        return 'Phone';
    }

    protected function publicEmail(string $email): string
    {
        if (str_contains(strtolower($email), '@guest.solqam.local')) {
            return '';
        }

        return $email;
    }

    protected function payLabel(string $method): string
    {
        return match (strtolower($method)) {
            'jazzcash' => 'JazzCash',
            'easypaisa' => 'Easypaisa',
            'card', 'payfast' => 'Card',
            'wallet' => 'Wallet',
            'pay_later' => 'Pay later',
            default => 'COD',
        };
    }
}
