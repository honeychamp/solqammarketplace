<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\OrderItemModel;
use App\Models\OrderModel;
use App\Services\Order\OrderService;

class OrderController extends BaseController
{
    protected OrderModel $orderModel;
    protected OrderItemModel $orderItemModel;
    protected OrderService $orderService;

    public function __construct()
    {
        $this->orderModel     = new OrderModel();
        $this->orderItemModel = new OrderItemModel();
        $this->orderService   = new OrderService();
    }

    public function index()
    {
        return $this->renderOrderList(false);
    }

    public function export()
    {
        try {
            @ini_set('max_execution_time', '180');
            @ini_set('memory_limit', '512M');
            if (method_exists(\App\Services\Platform\SchemaHeal::class, 'ensureExportColumns')) {
                \App\Services\Platform\SchemaHeal::ensureExportColumns();
            }
            $svc = new \App\Services\Export\OrderExportService();
            $mine = str_contains((string) uri_string(), 'my-orders');
            $storeId = $mine ? (int) session()->get('user.id') : null;
            $filters = $svc->filtersFromRequest($this->request, $storeId);
            $stamp = date('Ymd_His');
            $name = $mine ? 'Solqam_Admin_Store_Orders_' : 'Solqam_Full_Orders_';

            if (($filters['format'] ?? 'xlsx') === 'csv') {
                return $svc->download(array_merge($filters, ['format' => 'csv']), $storeId, $name . $stamp);
            }

            $pack = $svc->build($filters, $storeId);

            return $this->sendOrderSpreadsheet(
                $pack,
                $name . $stamp,
                \App\Services\Export\OrderExportService::HEADERS,
                \App\Services\Export\OrderExportService::GROUPS
            );
        } catch (\Throwable $e) {
            log_message('error', 'Admin order export: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());

            return redirect()->to(site_url('admin/orders'))->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    /**
     * Excel XML (.xls) — no ZipArchive. WPS / Excel open this.
     *
     * @param array{rows: list<list<array{v:mixed,t:string}>>, summary: list<list<array{v:mixed,t:string}>>, pending: list<list<array{v:mixed,t:string}>>} $pack
     */
    public function sendOrderSpreadsheet(array $pack, string $filenameBase, array $headers, array $groups)
    {
        $esc = static function ($v): string {
            return htmlspecialchars((string) $v, ENT_XML1 | ENT_QUOTES, 'UTF-8') ?: '';
        };
        $cell = static function (array $c) use ($esc): string {
            $v = $c['v'] ?? '';
            if (($c['t'] ?? 's') === 'n' && $v !== '' && $v !== null && is_numeric($v)) {
                return '<Cell ss:StyleID="n"><Data ss:Type="Number">' . (0 + $v) . '</Data></Cell>';
            }

            return '<Cell ss:StyleID="t"><Data ss:Type="String">' . $esc($v) . '</Data></Cell>';
        };

        $sheets = [
            ['name' => 'Orders', 'headers' => $headers, 'rows' => $pack['rows'] ?? [], 'groups' => $groups],
            ['name' => 'Summary', 'headers' => ['Metric', 'Value'], 'rows' => $pack['summary'] ?? [], 'groups' => null],
            ['name' => 'Pending Dispatch', 'headers' => $headers, 'rows' => $pack['pending'] ?? [], 'groups' => $groups],
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<?mso-application progid="Excel.Sheet"?>' . "\n"
            . '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"'
            . ' xmlns:o="urn:schemas-microsoft-com:office:office"'
            . ' xmlns:x="urn:schemas-microsoft-com:office:excel"'
            . ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">'
            . '<Styles>'
            . '<Style ss:ID="hdr"><Font ss:Bold="1"/><Interior ss:Color="#FFF2CC" ss:Pattern="Solid"/></Style>'
            . '<Style ss:ID="grp"><Font ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#0B30E6" ss:Pattern="Solid"/></Style>'
            . '<Style ss:ID="n"><NumberFormat ss:Format="0.00"/></Style>'
            . '<Style ss:ID="t"><NumberFormat ss:Format="@"/></Style>'
            . '</Styles>';

        foreach ($sheets as $sheet) {
            $xml .= '<Worksheet ss:Name="' . $esc(substr((string) $sheet['name'], 0, 31)) . '"><Table>';
            if (! empty($sheet['groups'])) {
                $xml .= '<Row>';
                foreach ($sheet['groups'] as $g) {
                    $span = max(1, (int) ($g['span'] ?? 1));
                    $xml .= '<Cell ss:StyleID="grp"' . ($span > 1 ? ' ss:MergeAcross="' . ($span - 1) . '"' : '')
                        . '><Data ss:Type="String">' . $esc($g['label'] ?? '') . '</Data></Cell>';
                }
                $xml .= '</Row>';
            }
            $xml .= '<Row>';
            foreach ($sheet['headers'] as $h) {
                $xml .= '<Cell ss:StyleID="hdr"><Data ss:Type="String">' . $esc($h) . '</Data></Cell>';
            }
            $xml .= '</Row>';
            foreach ($sheet['rows'] as $row) {
                $xml .= '<Row>';
                foreach ($row as $c) {
                    $xml .= $cell($c);
                }
                $xml .= '</Row>';
            }
            $xml .= '</Table></Worksheet>';
        }
        $xml .= '</Workbook>';

        return service('response')
            ->setHeader('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filenameBase . '.xls"')
            ->setHeader('Cache-Control', 'no-store')
            ->setBody($xml);
    }

    public function mine()
    {
        return $this->renderOrderList(true);
    }

    protected function renderOrderList(bool $mineOnly)
    {
        $adminId = (int) session()->get('user.id');
        $status  = $this->request->getGet('status');
        $builder = $this->orderModel
            ->select('orders.*, users.name as customer_name, users.phone as customer_phone')
            ->join('users', 'users.id = orders.user_id')
            ->orderBy('orders.id', 'DESC');

        if ($mineOnly) {
            $idRows = $this->orderItemModel
                ->select('order_id')
                ->where('seller_id', $adminId)
                ->groupBy('order_id')
                ->findAll();
            $ids = array_column($idRows, 'order_id');
            if (empty($ids)) {
                $builder->where('orders.id', 0);
            } else {
                $builder->whereIn('orders.id', $ids);
            }
        }

        if (!empty($status)) {
            $builder->where('orders.status', $status);
        }

        hub_apply_search($builder, [
            'orders.order_number',
            'orders.status',
            'users.name',
            'users.phone',
        ], 'orders.id');

        $orders = $builder->paginate(25);

        foreach ($orders as &$order) {
            $items = $this->orderItemModel
                ->select('order_items.seller_id, order_items.fulfillment_status, seller_profiles.store_name, users.name as seller_name')
                ->join('seller_profiles', 'seller_profiles.user_id = order_items.seller_id', 'left')
                ->join('users', 'users.id = order_items.seller_id', 'left')
                ->where('order_items.order_id', $order['id'])
                ->groupBy('order_items.seller_id, order_items.fulfillment_status, seller_profiles.store_name, users.name')
                ->findAll();
            $order['sellers'] = $items;
        }
        unset($order);

        return view('admin/orders/index', [
            'title'         => $mineOnly ? 'My Orders — Solqam Admin Store' : 'Orders Monitor — Solqam Admin Console',
            'orders'        => $orders,
            'currentStatus' => $status,
            'mineOnly'      => $mineOnly,
            'pager'         => $this->orderModel->pager,
        ]);
    }

    public function show($id)
    {
        $adminId = (int) session()->get('user.id');
        $order   = $this->orderModel->getOrderDetail((int) $id);

        if (!$order) {
            return redirect()->to('/admin/orders')->with('error', 'Order not found.');
        }

        $hasAdminItems = false;
        foreach ($order['items'] as &$item) {
            $item['is_admin_item'] = ((int) ($item['seller_id'] ?? 0) === $adminId);
            if ($item['is_admin_item']) {
                $hasAdminItems = true;
            }
        }
        unset($item);

        return view('admin/orders/show', [
            'title'         => "Order #{$order['order_number']} — Solqam Admin",
            'order'         => $order,
            'adminId'       => $adminId,
            'hasAdminItems' => $hasAdminItems,
            'fromMine'      => $this->request->getGet('from') === 'mine',
            'codDues'       => (new \App\Services\Commission\SellerDueService())->forOrder((int) $id),
            'siblings'      => ($order['status'] ?? '') === 'placed'
                ? $this->orderService->siblingPlacedOrders((int) $id)
                : [],
            'deskSellerIds' => $this->orderService->solqamDeskSellerIds((int) $id, $adminId),
        ]);
    }

    public function editPlaced($id)
    {
        $qty = $this->request->getPost('qty') ?? [];
        $quantities = [];
        if (is_array($qty)) {
            foreach ($qty as $itemId => $n) {
                $quantities[(int) $itemId] = (int) $n;
            }
        }
        $address = [
            'recipient_name'  => (string) $this->request->getPost('recipient_name'),
            'phone'           => (string) $this->request->getPost('phone'),
            'street_address'  => (string) $this->request->getPost('street_address'),
            'city'            => (string) $this->request->getPost('city'),
            'province'        => (string) $this->request->getPost('province'),
            'postal_code'     => (string) $this->request->getPost('postal_code'),
            'alternate_phone' => (string) $this->request->getPost('alternate_phone'),
            'area'            => (string) $this->request->getPost('area'),
            'landmark'        => (string) $this->request->getPost('landmark'),
        ];
        $notes = (string) $this->request->getPost('notes');
        $back = $this->orderBack((int) $id);
        try {
            $adminId = (int) session()->get('user.id');
            $deskIds = $this->orderService->solqamDeskSellerIds((int) $id, $adminId);
            $this->orderService->editPlacedOrder((int) $id, $quantities, $address, $notes, $deskIds);

            return $this->redirectAfterHub($back, 'success', 'Solqam package updated. Confirm after call only confirms this package — the seller confirms their own self-deliver package.');
        } catch (\Throwable $e) {
            return $this->redirectAfterHub($back, 'error', $e->getMessage());
        }
    }

    public function cancelPlaced($id)
    {
        $back = $this->request->getPost('back_order')
            ? '/admin/orders/' . (int) $this->request->getPost('back_order')
            : $this->orderBack((int) $id);
        try {
            $order = $this->orderModel->find((int) $id);
            if (! $order || ($order['status'] ?? '') !== 'placed') {
                throw new \RuntimeException('Only a placed extra order can be cancelled.');
            }
            $this->orderService->updateOrderStatus((int) $id, 'cancelled', 'Cancelled after call — duplicate / not needed');

            return $this->redirectAfterHub($back, 'success', 'Extra order ' . ($order['order_number'] ?? '') . ' cancelled. Commission was not charged.');
        } catch (\Throwable $e) {
            return $this->redirectAfterHub($back, 'error', $e->getMessage());
        }
    }

    public function confirmCall($id)
    {
        $back = $this->orderBack((int) $id);
        try {
            $adminId = (int) session()->get('user.id');
            $deskIds = $this->orderService->solqamDeskSellerIds((int) $id, $adminId);
            if ($deskIds === []) {
                throw new \RuntimeException('This is a seller self-deliver order. The seller confirms it from Seller Hub.');
            }
            $shipments = [];
            foreach (($this->orderModel->getOrderDetail((int) $id)['shipments'] ?? []) as $pkg) {
                $shipments[(int) ($pkg['seller_id'] ?? 0)] = $pkg;
            }
            $n = 0;
            foreach ($deskIds as $sellerId) {
                $st = (string) (($shipments[$sellerId]['status'] ?? null) ?: 'placed');
                if ($st !== 'placed') {
                    continue;
                }
                $this->orderService->updateSellerShipment((int) $id, $sellerId, 'confirmed', [], true);
                $n++;
            }
            if ($n < 1) {
                throw new \RuntimeException('The Solqam package is already confirmed. Mark shipped / delivered on this page.');
            }

            return $this->redirectAfterHub($back, 'success', 'Call confirmed. Mark this Solqam package shipped and delivered on this page.');
        } catch (\Throwable $e) {
            return $this->redirectAfterHub($back, 'error', $e->getMessage());
        }
    }

    protected function orderBack(int $id): string
    {
        return $this->request->getPost('mine') ? '/admin/my-orders' : '/admin/orders/' . $id;
    }

    public function updateStatus($id)
    {
        $adminId       = (int) session()->get('user.id');
        $newStatus     = $this->request->getPost('status');
        $validStatuses = ['confirmed', 'shipped', 'delivered', 'cancelled', 'undelivered'];

        if (!in_array($newStatus, $validStatuses, true)) {
            return redirect()->back()->with('error', 'Invalid status value.');
        }

        $deskIds = $this->orderService->solqamDeskSellerIds((int) $id, $adminId);
        $target  = (int) $this->request->getPost('seller_id');
        if ($target < 1) {
            $target = $adminId;
        }
        if (! in_array($target, $deskIds, true)) {
            return $this->redirectAfterHub(
                '/admin/orders/' . $id,
                'error',
                'Admin does not run seller self-deliver packages. Confirm / ship / deliver here only after Send to Solqam.'
            );
        }

        try {
            if ($newStatus === 'cancelled') {
                $items = $this->orderItemModel->where('order_id', (int) $id)->findAll();
                $onlyDesk = true;
                foreach ($items as $row) {
                    if (! in_array((int) ($row['seller_id'] ?? 0), $deskIds, true)) {
                        $onlyDesk = false;
                        break;
                    }
                }
                if (! $onlyDesk) {
                    return $this->redirectAfterHub(
                        '/admin/orders/' . $id,
                        'error',
                        'This order also has seller self-deliver items. The whole order cannot be cancelled here.'
                    );
                }
                $this->orderService->updateOrderStatus((int) $id, 'cancelled');
            } else {
                $this->orderService->updateSellerShipment((int) $id, $target, $newStatus, [
                    'tracking_number' => $this->request->getPost('tracking_number'),
                    'courier'         => $this->request->getPost('courier'),
                ], true);
            }

            $msg = 'Solqam package is now ' . ucfirst($newStatus) . '.';
            if ($newStatus === 'delivered') {
                $msg .= ' Admin completed delivery.';
            }

            $back = $this->request->getPost('mine') ? '/admin/my-orders' : '/admin/orders/' . $id;

            return $this->redirectAfterHub($back, 'success', $msg);
        } catch (\Exception $e) {
            $back = $this->request->getPost('mine') ? '/admin/my-orders' : '/admin/orders/' . $id;

            return $this->redirectAfterHub($back, 'error', $e->getMessage());
        }
    }
}
