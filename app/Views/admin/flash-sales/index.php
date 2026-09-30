<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<h4 class="fw-bold mb-1">Campaigns</h4>
<p class="text-muted small mb-4">Flash deals (hours/days) and Mega Sale festivals. Sellers join from Seller Hub; you approve before the deal goes live.</p>

<div class="command-panel p-4 mb-4">
    <h6 class="fw-bold mb-3">Create campaign</h6>
    <form action="<?= site_url('admin/flash-sales/store') ?>" method="POST" class="row g-2">
        <?= csrf_field() ?>
        <div class="col-md-3"><input name="title" class="form-control" placeholder="e.g. 11.11 Mega Sale" required></div>
        <div class="col-md-2">
            <select name="campaign_type" class="form-select">
                <option value="flash">Flash Sale</option>
                <option value="mega">Mega Sale / Festival</option>
            </select>
        </div>
        <div class="col-md-2"><input type="datetime-local" name="starts_at" class="form-control" required></div>
        <div class="col-md-2"><input type="datetime-local" name="ends_at" class="form-control" required></div>
        <div class="col-md-3 d-flex align-items-center gap-2">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="seller_join" value="1" id="sellerJoin" checked>
                <label class="form-check-label small" for="sellerJoin">Sellers can join</label>
            </div>
            <button class="btn btn-primary ms-auto">Create</button>
        </div>
        <div class="col-12"><input name="rules_note" class="form-control" placeholder="Festival rules (optional) — min discount, stock, etc."></div>
    </form>
</div>

<?php if (!empty($pending)): ?>
<div class="command-panel p-4 mb-4">
    <h6 class="fw-bold mb-3">Seller applications (approve like Daraz)</h6>
    <?php foreach ($pending as $row): ?>
        <div class="d-flex flex-wrap justify-content-between align-items-center border-bottom py-2 gap-2">
            <div>
                <div class="fw-semibold"><?= esc($row['product_name'] ?? $row['name']) ?></div>
                <div class="small text-muted"><?= esc($row['campaign_title']) ?> · <?= esc($row['campaign_type']) ?> · <?= esc($row['seller_name'] ?? 'Seller') ?> · Rs. <?= number_format((float) $row['sale_price'], 0) ?> <span class="text-decoration-line-through">Rs. <?= number_format((float) $row['list_price'], 0) ?></span></div>
            </div>
            <div class="d-flex gap-2">
                <form action="<?= site_url('admin/flash-sales/item/' . $row['id'] . '/approve') ?>" method="POST"><?= csrf_field() ?><button class="btn btn-sm btn-success">Approve</button></form>
                <form action="<?= site_url('admin/flash-sales/item/' . $row['id'] . '/reject') ?>" method="POST"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger">Reject</button></form>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($active)): ?>
<div class="command-panel p-4 mb-4">
    <h6 class="fw-bold">Instant list (admin SKU) on: <?= esc($active['title']) ?></h6>
    <p class="small text-muted">First-party or any live product — goes live without a pending queue.</p>
    <form action="<?= site_url('admin/flash-sales/item') ?>" method="POST" class="row g-2">
        <?= csrf_field() ?>
        <input type="hidden" name="flash_sale_id" value="<?= (int) $active['id'] ?>">
        <div class="col-md-6">
            <select name="product_id" class="form-select" required>
                <?php foreach ($products as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= esc($p['name']) ?> (Rs. <?= number_format((float) $p['price'], 0) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3"><input type="number" step="0.01" name="sale_price" class="form-control" placeholder="Sale price" required></div>
        <div class="col-md-3"><button class="btn btn-warning w-100">Add deal</button></div>
    </form>
    <ul class="mt-3 mb-0">
        <?php foreach ($items as $item): ?>
            <li><?= esc($item['name']) ?> — Rs. <?= number_format((float) $item['sale_price'], 0) ?> <?= !empty($item['seller_name']) ? '· ' . esc($item['seller_name']) : '' ?></li>
        <?php endforeach; ?>
        <?php if (empty($items)): ?><li class="text-muted">No approved deals on this campaign yet.</li><?php endif; ?>
    </ul>
</div>
<?php endif; ?>

<div class="command-panel p-4">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Title</th><th>Type</th><th>Starts</th><th>Ends</th><th>Seller join</th></tr></thead>
            <tbody>
            <?php foreach ($sales as $s): ?>
                <tr>
                    <td><?= esc($s['title']) ?></td>
                    <td><?= esc($s['campaign_type'] ?? 'flash') ?></td>
                    <td><?= esc($s['starts_at']) ?></td>
                    <td><?= esc($s['ends_at']) ?></td>
                    <td><?= !empty($s['seller_join']) ? 'Yes' : 'No' ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($sales)): ?>
                <tr><td colspan="5" class="text-muted">No campaigns yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
