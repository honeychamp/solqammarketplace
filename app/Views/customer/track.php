<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container py-4" style="max-width: 720px;">
    <h3 class="fw-bold mb-3"><i class="bi bi-truck me-2 text-solqam"></i> Track My Order</h3>
    <form class="card border-0 shadow-sm rounded-4 p-4 mb-4" method="GET" action="<?= site_url('track') ?>">
        <label class="form-label fw-bold">Order number</label>
        <div class="input-group">
            <input type="text" name="order" class="form-control" placeholder="Order number" value="<?= esc($query) ?>">
            <button class="btn btn-solqam" type="submit">Track</button>
        </div>
    </form>
    <?php if ($query !== '' && !$order): ?>
        <div class="alert alert-warning">No order found for that number.</div>
    <?php elseif ($order): ?>
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="d-flex justify-content-between">
                <strong><?= esc($order['order_number']) ?></strong>
                <span class="badge bg-primary"><?= esc(ucfirst($order['status'])) ?></span>
            </div>
            <p class="small text-muted mt-2 mb-1">Courier: <?= esc($order['courier'] ?: 'Not assigned yet') ?></p>
            <p class="small text-muted">Tracking: <?= esc($order['tracking_number'] ?: 'Pending dispatch') ?></p>
            <a href="<?= site_url('account/orders/' . $order['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill mt-2">View full details</a>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
