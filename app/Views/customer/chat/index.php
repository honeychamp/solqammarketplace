<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container py-4" style="max-width: 760px;">
    <h4 class="fw-bold mb-3">Messages</h4>
    <?php if (empty($threads)): ?>
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
            <p class="text-muted mb-0">No chats yet. Open a product and tap Chat with seller.</p>
        </div>
    <?php else: ?>
        <div class="list-group shadow-sm rounded-4 overflow-hidden">
            <?php foreach ($threads as $t): ?>
                <a href="<?= site_url('messages/' . $t['id']) ?>" class="list-group-item list-group-item-action py-3">
                    <div class="d-flex justify-content-between">
                        <strong><?= esc($t['store_name'] ?: $t['seller_name'] ?? 'Seller') ?></strong>
                        <small class="text-muted"><?= $t['last_message_at'] ? date('d M H:i', strtotime($t['last_message_at'])) : '' ?></small>
                    </div>
                    <div class="small text-muted"><?= esc($t['product_name'] ?? 'Store chat') ?></div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
