<?= $this->extend('layouts/seller') ?>
<?= $this->section('content') ?>
<h4 class="fw-bold mb-3">Buyer inbox</h4>
<?php if (empty($threads)): ?>
    <div class="card border-0 shadow-sm rounded-4 p-5 text-center text-muted">No buyer messages yet.</div>
<?php else: ?>
    <div class="list-group shadow-sm rounded-4 overflow-hidden">
        <?php foreach ($threads as $t): ?>
            <a href="<?= site_url('seller/messages/' . $t['id']) ?>" class="list-group-item list-group-item-action py-3">
                <div class="d-flex justify-content-between">
                    <strong><?= esc($t['customer_name'] ?? 'Buyer') ?></strong>
                    <small class="text-muted"><?= $t['last_message_at'] ? date('d M H:i', strtotime($t['last_message_at'])) : '' ?></small>
                </div>
                <div class="small text-muted"><?= esc($t['product_name'] ?? 'Store chat') ?></div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>
