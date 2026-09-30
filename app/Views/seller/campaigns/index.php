<?= $this->extend('layouts/seller') ?>
<?= $this->section('content') ?>
<h4 class="fw-bold mb-1">Join campaigns</h4>
<p class="text-muted small mb-4">Like Daraz Seller Center: pick a live/upcoming Flash or Mega Sale, set a campaign price below your list price, then wait for admin approval.</p>

<?php if (empty($products)): ?>
    <div class="command-panel p-4 mb-4">List an active product first, then you can join deals.</div>
<?php elseif (empty($campaigns)): ?>
    <div class="command-panel p-4 mb-4">No open campaigns right now. Admin creates Flash Sale or Mega Sale first.</div>
<?php else: ?>
<div class="command-panel p-4 mb-4">
    <h6 class="fw-bold mb-3">Apply with a SKU</h6>
    <form action="<?= site_url('seller/campaigns/join') ?>" method="POST" class="row g-3">
        <?= csrf_field() ?>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Campaign</label>
            <select name="flash_sale_id" class="form-select" required>
                <?php foreach ($campaigns as $c): ?>
                    <option value="<?= (int) $c['id'] ?>">
                        <?= esc($c['title']) ?> · <?= esc($c['campaign_type'] ?? 'flash') ?>
                        (<?= esc($c['starts_at']) ?> → <?= esc($c['ends_at']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Your product</label>
            <select name="product_id" class="form-select" required>
                <?php foreach ($products as $p): ?>
                    <option value="<?= (int) $p['id'] ?>"><?= esc($p['name']) ?> — Rs. <?= number_format((float) $p['price'], 0) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold">Campaign price</label>
            <input type="number" step="0.01" min="1" name="sale_price" class="form-control" required>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button class="btn btn-solqam w-100">Submit</button>
        </div>
    </form>
    <?php foreach ($campaigns as $c): ?>
        <?php if (!empty($c['rules_note'])): ?>
            <p class="small text-muted mt-3 mb-0"><strong><?= esc($c['title']) ?> rules:</strong> <?= esc($c['rules_note']) ?></p>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="command-panel p-4">
    <h6 class="fw-bold mb-3">Your submissions</h6>
    <?php if (empty($mine)): ?>
        <p class="text-muted mb-0">None yet.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>Campaign</th><th>Product</th><th>Deal</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($mine as $row): ?>
                    <tr>
                        <td><?= esc($row['title']) ?> <span class="badge text-bg-light"><?= esc($row['campaign_type']) ?></span></td>
                        <td><?= esc($row['product_name']) ?></td>
                        <td>Rs. <?= number_format((float) $row['sale_price'], 0) ?> <span class="text-muted text-decoration-line-through small">Rs. <?= number_format((float) $row['list_price'], 0) ?></span></td>
                        <td><?= esc($row['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
