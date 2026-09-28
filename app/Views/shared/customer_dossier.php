<?= $this->extend($mode === 'admin' ? 'layouts/admin' : 'layouts/seller') ?>
<?= $this->section('content') ?>
<?php
$d = $dossier;
$u = $d['user'];
$t = $d['totals'];
$isAdmin = $mode === 'admin';
$back = $isAdmin ? site_url('admin/customers') : site_url('seller/customers');
$orderUrl = $isAdmin ? 'admin/orders/' : 'seller/orders/';
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <a href="<?= $back ?>" class="small text-decoration-none text-muted"><i class="bi bi-arrow-left"></i> All buyers</a>
        <h3 class="fw-black mb-1 mt-1"><?= esc($u['name']) ?></h3>
        <p class="text-muted mb-0"><?= $isAdmin ? 'Full customer 360' : 'Buyer activity on your store' ?> · <?= esc($u['email']) ?> · <?= esc($u['phone']) ?></p>
    </div>
    <span class="badge rounded-pill <?= ($u['status'] ?? '') === 'active' ? 'bg-success' : 'bg-danger' ?> px-3 py-2"><?= esc(ucfirst((string) ($u['status'] ?? 'active'))) ?></span>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-2">
        <div class="kpi-tile h-100"><span>Orders</span><strong><?= (int) $t['orders'] ?></strong></div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="kpi-tile h-100"><span><?= $isAdmin ? 'GMV' : 'Your goods' ?></span><strong>Rs. <?= number_format($isAdmin ? $t['gmv'] : $t['goods'], 0) ?></strong></div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="kpi-tile gold h-100"><span>Wallet used</span><strong>Rs. <?= number_format($t['wallet_used'], 0) ?></strong></div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="kpi-tile emerald h-100"><span>Cashback in</span><strong>Rs. <?= number_format($t['cashback'], 0) ?></strong></div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="kpi-tile accent h-100"><span>Commission</span><strong>Rs. <?= number_format($t['commission'], 0) ?></strong></div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="kpi-tile h-100"><span>Live wallet</span><strong>Rs. <?= number_format($d['wallet_balance'], 0) ?></strong></div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="command-panel p-4 mb-4">
            <h6 class="fw-bold mb-3">Profile</h6>
            <div class="small mb-2"><span class="text-muted">Name</span><div class="fw-semibold"><?= esc($u['name']) ?></div></div>
            <div class="small mb-2"><span class="text-muted">Email</span><div><?= esc($u['email']) ?></div></div>
            <div class="small mb-2"><span class="text-muted">Phone</span><div><?= esc($u['phone']) ?></div></div>
            <div class="small mb-2"><span class="text-muted">Joined</span><div><?= !empty($u['created_at']) ? date('d M Y', strtotime($u['created_at'])) : '—' ?></div></div>
            <div class="small"><span class="text-muted">Seller net (this view)</span><div class="fw-bold text-success">Rs. <?= number_format($t['net_to_seller'], 0) ?></div></div>
        </div>
        <div class="command-panel p-4 mb-4">
            <h6 class="fw-bold mb-3">Addresses</h6>
            <?php if (empty($d['addresses'])): ?>
                <p class="small text-muted mb-0">No saved address.</p>
            <?php else: ?>
                <?php foreach ($d['addresses'] as $a): ?>
                    <div class="small border rounded-3 p-2 mb-2">
                        <strong><?= esc($a['recipient_name']) ?></strong>
                        <div><?= esc($a['street_address']) ?></div>
                        <div><?= esc($a['city']) ?>, <?= esc($a['province']) ?></div>
                        <div><?= esc($a['phone']) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php if ($isAdmin && !empty($d['pay_later'])): ?>
        <div class="command-panel p-4 mb-4">
            <h6 class="fw-bold mb-3">Pay later KYC</h6>
            <?php foreach ($d['pay_later'] as $pl): ?>
                <div class="small mb-2">
                    <?= esc($pl['full_name']) ?> · <?= esc($pl['cnic_number']) ?>
                    <div><a href="<?= esc($pl['cnic_front_path']) ?>" target="_blank">CNIC</a> · <a href="<?= esc($pl['utility_bill_path']) ?>" target="_blank">Bill</a></div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <div class="col-lg-8">
        <div class="command-panel p-4 mb-4">
            <h6 class="fw-bold mb-3">Orders — items, wallet, commission</h6>
            <?php if (empty($d['orders'])): ?>
                <p class="text-muted mb-0">No orders.</p>
            <?php else: ?>
                <?php foreach ($d['orders'] as $o): ?>
                    <div class="border rounded-4 p-3 mb-3">
                        <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
                            <div>
                                <a class="fw-bold text-decoration-none" href="<?= site_url($orderUrl . $o['id']) ?>"><?= esc($o['order_number']) ?></a>
                                <div class="small text-muted"><?= date('d M Y H:i', strtotime($o['created_at'])) ?> · <?= esc(strtoupper((string) ($o['payment_method'] ?? ''))) ?> · <?= esc(ucfirst((string) $o['status'])) ?></div>
                            </div>
                            <div class="text-end small">
                                <div>Order total Rs. <?= number_format((float) $o['total_amount'], 0) ?></div>
                                <div class="text-success">Wallet used Rs. <?= number_format((float) $o['wallet_amount_used'], 0) ?></div>
                                <div>Cashback Rs. <?= number_format((float) $o['cashback'], 0) ?></div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead><tr><th>Item</th><th>Qty</th><th>Goods</th><th>Cashback</th><th>Commission</th><th>Net</th></tr></thead>
                                <tbody>
                                <?php foreach ($o['items'] as $it): ?>
                                    <tr>
                                        <td><?= esc($it['product_name']) ?></td>
                                        <td><?= (int) $it['quantity'] ?></td>
                                        <td>Rs. <?= number_format((float) $it['subtotal'], 0) ?></td>
                                        <td class="text-success">Rs. <?= number_format(item_cashback($it), 0) ?></td>
                                        <td class="text-danger">Rs. <?= number_format((float) $it['commission_amount'], 0) ?></td>
                                        <td class="text-success">Rs. <?= number_format(item_seller_net($it), 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="command-panel p-4">
            <h6 class="fw-bold mb-3">Wallet ledger<?= $isAdmin ? '' : ' (orders with your store)' ?></h6>
            <?php if (empty($d['ledger'])): ?>
                <p class="small text-muted mb-0">No ledger rows.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>When</th><th>Type</th><th>Ref</th><th class="text-end">Amount</th></tr></thead>
                        <tbody>
                        <?php foreach ($d['ledger'] as $row): ?>
                            <tr>
                                <td class="small"><?= esc($row['created_at'] ?? '') ?></td>
                                <td><span class="badge text-bg-light"><?= esc($row['type']) ?> / <?= esc($row['reference_type']) ?></span></td>
                                <td class="small"><?= esc($row['description'] ?? '') ?></td>
                                <td class="text-end fw-semibold <?= ($row['type'] ?? '') === 'credit' ? 'text-success' : 'text-danger' ?>">
                                    <?= ($row['type'] ?? '') === 'credit' ? '+' : '-' ?> Rs. <?= number_format((float) $row['amount'], 0) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
