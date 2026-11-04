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
            return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8') ?: '';
        };

        $sheets = [
            ['name' => 'Orders', 'headers' => $headers, 'rows' => $pack['rows'] ?? [], 'groups' => $groups],
            ['name' => 'Summary', 'headers' => ['Metric', 'Value'], 'rows' => $pack['summary'] ?? [], 'groups' => null],
            ['name' => 'Pending Dispatch', 'headers' => $headers, 'rows' => $pack['pending'] ?? [], 'groups' => $groups],
        ];

        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">'
            . '<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">'
            . '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets>';
        foreach ($sheets as $sheet) {
            $html .= '<x:ExcelWorksheet><x:Name>' . $esc($sheet['name']) . '</x:Name>'
                . '<x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet>';
        }
        $html .= '</x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->'
            . '<style>td,th{border:1px solid #ccc} .n{mso-number-format:0.00} .t{mso-number-format:"\\@"}</style></head><body>';

        foreach ($sheets as $i => $sheet) {
            if ($i > 0) {
                $html .= '<br style="page-break-before:always">';
            }
            $html .= '<table border="1">';
            if (! empty($sheet['groups'])) {
                $html .= '<tr>';
                foreach ($sheet['groups'] as $g) {
                    $html .= '<th colspan="' . max(1, (int) ($g['span'] ?? 1)) . '">' . $esc($g['label'] ?? '') . '</th>';
                }
                $html .= '</tr>';
            }
            $html .= '<tr>';
            foreach ($sheet['headers'] as $h) {
                $html .= '<th>' . $esc($h) . '</th>';
            }
            $html .= '</tr>';
            foreach ($sheet['rows'] as $row) {
                $html .= '<tr>';
                foreach ($row as $cell) {
                    $v = $cell['v'] ?? '';
                    if (($cell['t'] ?? 's') === 'n' && $v !== '' && $v !== null && is_numeric($v)) {
                        $html .= '<td class="n">' . (0 + $v) . '</td>';
                    } else {
                        $html .= '<td class="t">' . $esc($v) . '</td>';
                    }
                }
                $html .= '</tr>';
            }
            $html .= '</table>';
        }
        $html .= '</body></html>';

        return service('response')
            ->setHeader('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filenameBase . '.xls"')
            ->setHeader('Cache-Control', 'no-store')
            ->setBody(chr(0xEF) . chr(0xBB) . chr(0xBF) . $html);
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
        ]);
    }

    public function updateStatus($id)
    {
        $adminId       = (int) session()->get('user.id');
        $newStatus     = $this->request->getPost('status');
        $validStatuses = ['placed', 'confirmed', 'shipped', 'delivered', 'cancelled', 'undelivered'];

        if (!in_array($newStatus, $validStatuses, true)) {
            return redirect()->back()->with('error', 'Invalid status value.');
        }

        $items       = $this->orderItemModel->where('order_id', (int) $id)->findAll();
        $adminItems  = array_filter($items, static fn ($row) => (int) $row['seller_id'] === $adminId);
        $sellerItems = array_filter($items, static fn ($row) => (int) $row['seller_id'] !== $adminId);

        if ($adminItems === []) {
            return $this->redirectAfterHub(
                '/admin/orders/' . $id,
                'error',
                'You can only change fulfillment on your own products. Seller packages are view-only.'
            );
        }

        try {
            if ($sellerItems === []) {
                $this->orderService->updateOrderStatus((int) $id, $newStatus);
            } else {
                if ($newStatus === 'cancelled') {
                    return $this->redirectAfterHub(
                        '/admin/orders/' . $id,
                        'error',
                        'This order also has seller items. Cancel is not allowed from admin for mixed orders.'
                    );
                }
                if ($newStatus === 'placed') {
                    return $this->redirectAfterHub('/admin/orders/' . $id, 'error', 'Use Confirmed, Shipped, or Delivered for your package.');
                }
                $this->orderService->updateSellerShipment((int) $id, $adminId, $newStatus);
            }

            $msg = 'Your product package is now ' . ucfirst($newStatus) . '.';
            if ($newStatus === 'delivered') {
                $msg .= ' Buyer gets the cashback % set on each product when the full order is delivered. No commission on your store items.';
            }

            $back = $this->request->getPost('mine') ? '/admin/my-orders' : '/admin/orders/' . $id;

            return $this->redirectAfterHub($back, 'success', $msg);
        } catch (\Exception $e) {
            $back = $this->request->getPost('mine') ? '/admin/my-orders' : '/admin/orders/' . $id;

            return $this->redirectAfterHub($back, 'error', $e->getMessage());
        }
    }
}
