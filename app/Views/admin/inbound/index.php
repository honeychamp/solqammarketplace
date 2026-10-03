<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Seller inbound</h4>
        <p class="text-secondary small mb-0">Sellers who chose Solqam delivery. Receive the parcel, then ship to the buyer. You keep the delivery fee; category commission is still taken from product value only.</p>
    </div>
</div>
<div class="card-custom p-4">
    <?php if (empty($packages)): ?>
        <p class="text-muted mb-0">No seller packages waiting for Solqam delivery.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light small">
                    <tr>
                        <th>Order</th>
                        <th>Seller</th>
                        <th>Handoff</th>
                        <th>Status</th>
                        <th>Delivery fee</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($packages as $p): ?>
                    <tr>
                        <td>
                            <a href="<?= site_url('admin/orders/' . $p['order_id']) ?>"><?= esc($p['order_number']) ?></a>
                        </td>
                        <td><?= esc($p['store_name'] ?: $p['seller_name']) ?></td>
                        <td><span class="badge text-bg-warning"><?= esc($p['handoff_status'] ?: 'pending_admin') ?></span></td>
                        <td><?= esc(ucfirst((string) $p['status'])) ?></td>
                        <td>Rs. <?= number_format((float) $p['shipping_amount'], 0) ?></td>
                        <td class="text-end">
                            <?php if (($p['handoff_status'] ?? '') !== 'received'): ?>
                                <form action="<?= site_url('admin/inbound/' . $p['id'] . '/receive') ?>" method="POST" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-success rounded-pill">Mark received</button>
                                </form>
                            <?php else: ?>
                                <form action="<?= site_url('admin/inbound/' . $p['id'] . '/status') ?>" method="POST" class="d-flex flex-wrap justify-content-end gap-1">
                                    <?= csrf_field() ?>
                                    <select name="status" class="form-select form-select-sm" style="width:130px">
                                        <option value="shipped">Shipped</option>
                                        <option value="delivered">Delivered</option>
                                        <option value="undelivered">Not received</option>
                                    </select>
                                    <input type="text" name="courier" class="form-control form-control-sm" placeholder="Courier" style="width:110px">
                                    <input type="text" name="tracking_number" class="form-control form-control-sm" placeholder="Tracking" style="width:110px">
                                    <button class="btn btn-sm btn-primary">Update</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
