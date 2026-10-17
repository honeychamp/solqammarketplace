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
            <li class="d-flex justify-content-between align-items-center gap-2 py-1">
                <span><?= esc($item['name']) ?> — Rs. <?= number_format((float) $item['sale_price'], 0) ?> <?= !empty($item['seller_name']) ? '· ' . esc($item['seller_name']) : '' ?></span>
                <a href="<?= site_url('admin/flash-sales/item/' . $item['id'] . '/delete') ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this deal?');">Delete</a>
            </li>
        <?php endforeach; ?>
        <?php if (empty($items)): ?><li class="text-muted">No approved deals on this campaign yet.</li><?php endif; ?>
    </ul>
</div>
<?php endif; ?>

<div class="command-panel p-4">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Title</th><th>Type</th><th>Starts</th><th>Ends</th><th>Seller join</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($sales as $s): ?>
                <?php $toLocal = static fn ($v) => $v ? str_replace(' ', 'T', substr((string) $v, 0, 16)) : ''; ?>
                <tr>
                    <td colspan="6" class="p-2">
                        <form action="<?= site_url('admin/flash-sales/update/' . $s['id']) ?>" method="POST" class="row g-2 align-items-end">
                            <?= csrf_field() ?>
                            <div class="col-md-3"><input name="title" class="form-control form-control-sm" value="<?= esc($s['title']) ?>" required></div>
                            <div class="col-md-2">
                                <select name="campaign_type" class="form-select form-select-sm">
                                    <option value="flash" <?= ($s['campaign_type'] ?? 'flash') === 'flash' ? 'selected' : '' ?>>Flash Sale</option>
                                    <option value="mega" <?= ($s['campaign_type'] ?? '') === 'mega' ? 'selected' : '' ?>>Mega Sale</option>
                                </select>
                            </div>
                            <div class="col-md-2"><input type="datetime-local" name="starts_at" class="form-control form-control-sm" value="<?= esc($toLocal($s['starts_at'] ?? '')) ?>"></div>
                            <div class="col-md-2"><input type="datetime-local" name="ends_at" class="form-control form-control-sm" value="<?= esc($toLocal($s['ends_at'] ?? '')) ?>"></div>
                            <div class="col-md-1">
                                <select name="is_active" class="form-select form-select-sm">
                                    <option value="1" <?= !empty($s['is_active']) ? 'selected' : '' ?>>On</option>
                                    <option value="0" <?= empty($s['is_active']) ? 'selected' : '' ?>>Off</option>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-center gap-2">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" name="seller_join" value="1" <?= !empty($s['seller_join']) ? 'checked' : '' ?>>
                                    <label class="form-check-label small">Sellers</label>
                                </div>
                                <button class="btn btn-sm btn-primary">Save</button>
                                <a href="<?= site_url('admin/flash-sales/delete/' . $s['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this campaign and its deals?');">Delete</a>
                            </div>
                            <div class="col-12"><input name="rules_note" class="form-control form-control-sm" value="<?= esc($s['rules_note'] ?? '') ?>" placeholder="Festival rules (optional)"></div>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($sales)): ?>
                <tr><td colspan="6" class="text-muted">No campaigns yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?= view('shared/_pager', ['pager' => $pager ?? null]) ?>
</div>
<?= $this->endSection() ?>
