<?php

namespace App\Services\Export;

use Config\Database;

class OrderExportService
{
    public const GROUPS = [
        ['label' => 'A. Main', 'span' => 18],
        ['label' => 'B. Extra', 'span' => 45],
    ];

    public const HEADERS = [
        'Order Date', 'Order #', 'Customer Name', 'Phone (Primary)', 'Phone (Alternate / WhatsApp)', 'Full Address', 'Nearest Landmark', 'Area / Locality', 'City', 'Province', 'Address Status', 'SKU', 'Product Name', 'Variant', 'Quantity (Units)', 'Unit Price (Rs)', 'Shipping Charged to Customer (Rs)', 'Grand Total (Rs)',
        'Order Time (PKT)', 'Order Day', 'Order Status', 'Confirmation Status', 'Store Name', 'Store ID', 'Email', 'Customer Type', 'Total Past Orders', 'Postal Code', 'Line Total (Rs)', 'Category', 'Gift Wrap', 'Gift Message', 'Gift Wrap Charge (Rs)', 'Packing Type', 'Payment Method', 'Payment Status', 'Subtotal (Rs)', 'Discount / Coupon Code', 'Discount Amount (Rs)', 'Cashback (Rs)', 'Commission (Rs)', 'Courier', 'Tracking Number', 'Shipping Method', 'Dispatch Date', 'Delivered Date', 'Seller Contact', 'Order Source', 'Order Type', 'Login Method', 'Total Products in Order', 'Total Pieces in Order', 'Packing Cost (Rs)', 'Package Weight (kg)', 'Actual Shipping Cost (Rs)', 'Product Cost (Rs)', 'Net Profit (Rs)', 'Supplier / Source', 'Customer Note / Instruction', 'Preferred Delivery Time', 'Internal Admin Note', 'Cancel / Return Reason', 'Last Updated',
    ];

    public function filtersFromRequest($request, ?int $sellerId = null): array
    {
        $preset = strtolower(trim((string) $request->getGet('preset')));
        $from   = $this->validYmd($request->getGet('from'));
        $to     = $this->validYmd($request->getGet('to'));
        $fromYm = $this->monthStart($request->getGet('from_year'), $request->getGet('from_month'));
        $toYm   = $this->monthEnd($request->getGet('to_year'), $request->getGet('to_month'));
        if ($from === '' && $fromYm !== '') {
            $from = $fromYm;
        }
        if ($to === '' && $toYm !== '') {
            $to = $toYm;
        }

        $tz    = new \DateTimeZone('Asia/Karachi');
        $today = new \DateTimeImmutable('now', $tz);

        if ($preset === 'all' || $preset === 'alltime') {
            $from = '';
            $to   = '';
        } elseif ($preset === 'today') {
            $from = $today->format('Y-m-d');
            $to   = $today->format('Y-m-d');
        } elseif ($preset === 'yesterday') {
            $d    = $today->modify('-1 day');
            $from = $d->format('Y-m-d');
            $to   = $d->format('Y-m-d');
        } elseif ($preset === '7d') {
            $from = $today->modify('-6 days')->format('Y-m-d');
            $to   = $today->format('Y-m-d');
        } elseif ($preset === 'month') {
            $from = $today->modify('first day of this month')->format('Y-m-d');
            $to   = $today->format('Y-m-d');
        } elseif ($preset === 'last_month') {
            $start = $today->modify('first day of last month');
            $from  = $start->format('Y-m-d');
            $to    = $start->modify('last day of this month')->format('Y-m-d');
        } elseif ($preset === '3m') {
            $from = $today->modify('-3 months')->format('Y-m-d');
            $to   = $today->format('Y-m-d');
        } elseif ($preset === '6m') {
            $from = $today->modify('-6 months')->format('Y-m-d');
            $to   = $today->format('Y-m-d');
        } elseif ($preset === 'year') {
            $from = $today->format('Y-01-01');
            $to   = $today->format('Y-m-d');
        } elseif ($preset === 'last_year') {
            $y    = (int) $today->format('Y') - 1;
            $from = $y . '-01-01';
            $to   = $y . '-12-31';
        }

        if ($from !== '' && $to !== '' && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [
            'from'           => $from,
            'to'             => $to,
            'status'         => trim((string) $request->getGet('status')),
            'city'           => trim((string) $request->getGet('city')),
            'payment_method' => trim((string) $request->getGet('payment_method')),
            'store'          => $sellerId ?: (int) $request->getGet('store'),
            'hide_test'      => $request->getGet('hide_test') === '1' ? 1 : 0,
            'format'         => strtolower((string) $request->getGet('format')) === 'csv' ? 'csv' : 'xlsx',
        ];
    }

    protected function validYmd($value): string
    {
        $v = trim((string) $value);
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
            return '';
        }
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $v);

        return ($dt && $dt->format('Y-m-d') === $v) ? $v : '';
    }

    protected function monthStart($year, $month): string
    {
        $y = (int) $year;
        $m = (int) $month;
        if ($y < 2000 || $y > 2100 || $m < 1 || $m > 12) {
            return '';
        }

        return sprintf('%04d-%02d-01', $y, $m);
    }

    protected function monthEnd($year, $month): string
    {
        $start = $this->monthStart($year, $month);
        if ($start === '') {
            return '';
        }

        return (new \DateTimeImmutable($start))->modify('last day of this month')->format('Y-m-d');
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
        @ini_set('max_execution_time', '180');
        @ini_set('memory_limit', '512M');
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
        $of = $this->tableFields($db, 'orders');
        $orderBits = [
            'orders.id as order_pk',
            'orders.order_number',
            'orders.user_id',
            'orders.status as order_status',
            'orders.created_at as order_created',
            'orders.updated_at as order_updated',
            'orders.total_amount',
            'orders.discount_amount',
            'orders.final_payable',
            'orders.notes',
            'orders.address_id',
        ];
        foreach ([
            'shipping_amount', 'coupon_code', 'coupon_discount', 'cashback_amount',
            'tracking_number', 'courier', 'delivery_date',
        ] as $col) {
            if (isset($of[$col])) {
                $orderBits[] = 'orders.' . $col;
            }
        }
        if (isset($of['commission_amount'])) {
            $orderBits[] = 'orders.commission_amount as order_commission';
        }

        $select = 'order_items.*, ' . implode(', ', $orderBits)
            . ', users.name as customer_name, users.email as customer_email, users.phone as user_phone'
            . ', addresses.recipient_name, addresses.phone as recipient_phone, addresses.street_address, addresses.city, addresses.province, addresses.postal_code'
            . ', seller_profiles.store_name, su.name as seller_name, su.phone as seller_phone'
            . ', products.sku as product_sku, products.brand, categories.name as category_name';

        $applyFilters = function ($builder) use ($filters, $sellerOnlyId, $db) {
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
            if (($filters['payment_method'] ?? '') !== '' && $db->tableExists('payments')) {
                $builder->where(
                    'orders.id IN (SELECT p.order_id FROM payments p WHERE p.payment_method = '
                    . $db->escape($filters['payment_method']) . ')',
                    null,
                    false
                );
            }
            $from = $this->validYmd($filters['from'] ?? '');
            $to   = $this->validYmd($filters['to'] ?? '');
            if ($from !== '') {
                $builder->where('orders.created_at >=', $from . ' 00:00:00');
            }
            if ($to !== '') {
                $builder->where('orders.created_at <=', $to . ' 23:59:59');
            }
            if (! empty($filters['hide_test'])) {
                $builder->groupStart()
                    ->where('orders.notes', null)
                    ->orWhere('orders.notes', '')
                    ->orNotLike('orders.notes', 'TEST')
                    ->groupEnd();
            }
            $builder->orderBy('orders.created_at', 'DESC')->orderBy('order_items.id', 'ASC');

            return $builder;
        };

        try {
            $builder = $applyFilters(
                $db->table('order_items')
                    ->select($select, false)
                    ->join('orders', 'orders.id = order_items.order_id')
                    ->join('users', 'users.id = orders.user_id', 'left')
                    ->join('addresses', 'addresses.id = orders.address_id', 'left')
                    ->join('seller_profiles', 'seller_profiles.user_id = order_items.seller_id', 'left')
                    ->join('users as su', 'su.id = order_items.seller_id', 'left')
                    ->join('products', 'products.id = order_items.product_id', 'left')
                    ->join('categories', 'categories.id = products.category_id', 'left')
            );
            $rows = $builder->get()->getResultArray();
        } catch (\Throwable $e) {
            log_message('error', 'Order export query: ' . $e->getMessage());
            try {
                $builder = $applyFilters(
                    $db->table('order_items')
                        ->select('order_items.*, orders.order_number, orders.user_id, orders.status as order_status, orders.created_at as order_created, orders.updated_at as order_updated, orders.total_amount, orders.discount_amount, orders.final_payable, orders.notes, orders.address_id', false)
                        ->join('orders', 'orders.id = order_items.order_id')
                        ->join('addresses', 'addresses.id = orders.address_id', 'left')
                );
                $rows = $builder->get()->getResultArray();
            } catch (\Throwable $e2) {
                log_message('error', 'Order export fallback: ' . $e2->getMessage());
                throw new \RuntimeException('Order export query failed: ' . $e2->getMessage());
            }
        }

        $this->attachOptional($rows);

        return $rows;
    }

    /**
     * @return array<string,true>
     */
    protected function tableFields($db, string $table): array
    {
        try {
            return array_flip($db->getFieldNames($table) ?: []);
        } catch (\Throwable $e) {
            return [];
        }
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
        $pays = [];
        try {
            if ($db->tableExists('payments')) {
                foreach ($db->table('payments')->whereIn('order_id', $orderIds)->orderBy('id', 'DESC')->get()->getResultArray() as $p) {
                    $oid = (int) $p['order_id'];
                    if (! isset($pays[$oid])) {
                        $pays[$oid] = $p;
                    }
                }
            }
        } catch (\Throwable $e) {
        }

        foreach ($rows as &$row) {
            $oid = (int) $row['order_id'];
            $ord = $extraOrders[$oid] ?? [];
            $addr = $addrs[(int) ($ord['address_id'] ?? $row['address_id'] ?? 0)] ?? [];
            $prod = $products[(int) ($row['product_id'] ?? 0)] ?? [];
            $ship = $ships[$oid . ':' . (int) ($row['seller_id'] ?? 0)] ?? [];
            $row['_ord']  = $ord;
            $row['_addr'] = $addr;
            $row['_prod'] = $prod;
            $row['_ship'] = $ship;
            $row['_pay']  = $pays[$oid] ?? [];
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

        $pay = $line['_pay'] ?? [];
        $phone = $this->digitsPhone((string) ($addr['phone'] ?? $line['recipient_phone'] ?? $line['user_phone'] ?? ''));
        $name  = trim((string) ($addr['recipient_name'] ?? $line['recipient_name'] ?? $line['customer_name'] ?? ''));
        $street = trim((string) ($addr['street_address'] ?? $line['street_address'] ?? ''));
        $city = trim((string) ($addr['city'] ?? $line['city'] ?? ''));
        $email = (string) ($line['customer_email'] ?? '');
        $login = $this->loginMethod($line, $email);
        $past  = (int) ($phoneCounts[$phone] ?? 1);
        $qty   = (int) ($line['quantity'] ?? 0);
        $unit  = (float) ($line['price'] ?? 0);
        $lineTotal = (float) ($line['subtotal'] ?? ($unit * $qty));
        $giftYes = (int) ($line['gift_wrap'] ?? 0) === 1;
        $giftFee = (float) ($line['gift_wrap_fee'] ?? 0);
        $orderGift = (float) ($ord['gift_wrap_amount'] ?? $line['gift_wrap_amount'] ?? 0);
        $packCost = (float) ($prod['packing_cost'] ?? $ord['packing_cost'] ?? 0);
        $shipCharged = (float) ($ord['shipping_amount'] ?? $line['shipping_amount'] ?? 0);
        $shipActual = (float) ($ord['actual_shipping_cost'] ?? 0);
        $goods = (float) ($ord['total_amount'] ?? $line['total_amount'] ?? 0) - $shipCharged - $orderGift;
        if ($goods < 0) {
            $goods = (float) ($ord['total_amount'] ?? $line['total_amount'] ?? 0);
        }
        $discount = (float) ($ord['coupon_discount'] ?? $ord['discount_amount'] ?? $line['discount_amount'] ?? 0);
        $cashback = (float) ($ord['cashback_amount'] ?? $line['order_cashback'] ?? 0);
        $commission = (float) ($ord['commission_amount'] ?? $line['order_commission'] ?? $line['commission_amount'] ?? 0);
        $grand = (float) ($ord['final_payable'] ?? $line['final_payable'] ?? $line['total_amount'] ?? 0);
        $unitCost = (float) ($prod['product_cost'] ?? 0);
        $lineCost = $unitCost * $qty;
        $orderCost = $first ? $lineCost : 0;
        $net = $first ? round($grand - $orderCost - $packCost - $shipActual - $commission - $cashback, 2) : '';

        $courier = (string) ($ship['courier'] ?? $ord['courier'] ?? $line['courier'] ?? '');
        $track = (string) ($ship['tracking_number'] ?? $ord['tracking_number'] ?? $line['tracking_number'] ?? '');
        $status = (string) ($line['order_status'] ?? $ord['status'] ?? '');
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

        $assoc = [
            'Order #' => $s((string) ($line['order_number'] ?? '')),
            'Order Date' => $s($created['date']),
            'Order Time (PKT)' => $s($created['time']),
            'Order Day' => $s($created['day']),
            'Order Status' => $s(ucfirst($status)),
            'Confirmation Status' => $s((string) ($ord['confirmation_status'] ?? '')),
            'Store Name' => $s($adminHint),
            'Store ID' => $s($sid > 0 ? 'ST-' . str_pad((string) $sid, 3, '0', STR_PAD_LEFT) : ''),
            'Customer Name' => $s($name),
            'Phone (Primary)' => $s($phone),
            'Phone (Alternate / WhatsApp)' => $s($alt),
            'Email' => $s($this->publicEmail($email)),
            'Customer Type' => $s($past > 1 ? 'Returning' : 'New'),
            'Total Past Orders' => $n($past),
            'City' => $s($city),
            'Area / Locality' => $s($area),
            'Full Address' => $s($street),
            'Nearest Landmark' => $s($landmark),
            'Province' => $s((string) ($addr['province'] ?? $line['province'] ?? '')),
            'Postal Code' => $s((string) ($addr['postal_code'] ?? $line['postal_code'] ?? '')),
            'Address Status' => $s(strlen($street) >= 12 && $city !== '' ? 'Complete' : 'Incomplete'),
            'Product Name' => $s((string) ($line['product_name'] ?? '')),
            'Variant' => $s((string) ($line['variant_label'] ?? '')),
            'SKU' => $s((string) ($line['product_sku'] ?? $prod['sku'] ?? '')),
            'Quantity (Units)' => $n($qty),
            'Unit Price (Rs)' => $n($unit),
            'Line Total (Rs)' => $n($lineTotal),
            'Category' => $s((string) ($line['category_name'] ?? '')),
            'Gift Wrap' => $s($giftYes ? 'Yes' : 'No'),
            'Gift Message' => $s((string) ($line['gift_message'] ?? '')),
            'Gift Wrap Charge (Rs)' => $first ? $n($orderGift > 0 ? $orderGift : ($giftYes ? $giftFee : 0)) : $blankN,
            'Packing Type' => $s((string) ($prod['packing_type'] ?? $ord['packing_type'] ?? '')),
            'Grand Total (Rs)' => $first ? $n($grand) : $blankN,
            'Payment Method' => $s($this->payLabel((string) ($pay['payment_method'] ?? $line['payment_method'] ?? 'cod'))),
            'Payment Status' => $s(ucfirst((string) ($pay['status'] ?? $line['payment_status'] ?? 'pending'))),
            'Subtotal (Rs)' => $first ? $n($goods) : $blankN,
            'Shipping Charged to Customer (Rs)' => $first ? $n($shipCharged) : $blankN,
            'Discount / Coupon Code' => $s((string) ($ord['coupon_code'] ?? $line['coupon_code'] ?? '')),
            'Discount Amount (Rs)' => $first ? $n($discount) : $blankN,
            'Cashback (Rs)' => $first ? $n($cashback) : $blankN,
            'Commission (Rs)' => $first ? $n($commission) : $blankN,
            'Courier' => $s($courier),
            'Tracking Number' => $s($track),
            'Shipping Method' => $s((string) ($ord['shipping_method'] ?? 'Standard')),
            'Dispatch Date' => $s($dispatch),
            'Delivered Date' => $s($delivered),
            'Seller Contact' => $s($this->digitsPhone((string) ($line['seller_phone'] ?? ''))),
            'Order Source' => $s($source !== '' ? $source : 'Website'),
            'Order Type' => $s($type !== '' ? $type : 'Normal'),
            'Login Method' => $s($login),
            'Total Products in Order' => $first ? $n($meta['products']) : $blankN,
            'Total Pieces in Order' => $first ? $n($meta['pieces']) : $blankN,
            'Packing Cost (Rs)' => $first ? $n($packCost) : $blankN,
            'Package Weight (kg)' => $first ? $n((float) ($prod['weight'] ?? $ord['package_weight'] ?? 0) ?: '') : $blankN,
            'Actual Shipping Cost (Rs)' => $first ? $n($shipActual ?: '') : $blankN,
            'Product Cost (Rs)' => $first ? $n($orderCost ?: '') : $blankN,
            'Net Profit (Rs)' => $first ? (is_numeric($net) ? $n($net) : $blankN) : $blankN,
            'Supplier / Source' => $s((string) ($prod['supplier'] ?? '')),
            'Customer Note / Instruction' => $s((string) ($line['notes'] ?? '')),
            'Preferred Delivery Time' => $s((string) ($ord['preferred_delivery_time'] ?? '')),
            'Internal Admin Note' => $s((string) ($ord['internal_note'] ?? '')),
            'Cancel / Return Reason' => $s((string) ($ord['cancel_reason'] ?? '')),
            'Last Updated' => $s($updated['date'] !== '' ? $updated['date'] . ' ' . $updated['time'] : ''),
        ];

        return $this->orderedRow($assoc);
    }

    /**
     * @param array<string, array{v:mixed,t:string}> $assoc
     * @return list<array{v:mixed,t:string}>
     */
    protected function orderedRow(array $assoc): array
    {
        $out = [];
        foreach (self::HEADERS as $h) {
            $out[] = $assoc[$h] ?? ['v' => '', 't' => 's'];
        }

        return $out;
    }

    /**
     * @param list<array{v:mixed,t:string}> $row
     */
    protected function colVal(array $row, string $header): mixed
    {
        static $idx = null;
        if ($idx === null) {
            $idx = array_flip(self::HEADERS);
        }
        $i = $idx[$header] ?? null;

        return $i === null ? '' : ($row[$i]['v'] ?? '');
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
            $num = (string) $this->colVal($row, 'Order #');
            $first = $num !== $prev;
            $prev = $num;
            if ($first) {
                $orders[$num] = true;
                $sales += (float) $this->colVal($row, 'Grand Total (Rs)');
                $pack += (float) $this->colVal($row, 'Packing Cost (Rs)');
                $ship += (float) $this->colVal($row, 'Actual Shipping Cost (Rs)');
                $profit += (float) $this->colVal($row, 'Net Profit (Rs)');
                $city = (string) $this->colVal($row, 'City');
                $store = (string) $this->colVal($row, 'Store Name');
                $st = (string) $this->colVal($row, 'Order Status');
                $byCity[$city] = ($byCity[$city] ?? 0) + 1;
                $byStore[$store] = ($byStore[$store] ?? 0) + 1;
                $byStatus[$st] = ($byStatus[$st] ?? 0) + 1;
                if (strcasecmp((string) $this->colVal($row, 'Gift Wrap'), 'Yes') === 0) {
                    $giftN++;
                }
            }
            $pieces += (int) $this->colVal($row, 'Quantity (Units)');
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
            $dt = new \DateTimeImmutable($raw, $tz);

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
            return (new \DateTimeImmutable($raw, new \DateTimeZone('Asia/Karachi')))->format('d-M-Y');
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
