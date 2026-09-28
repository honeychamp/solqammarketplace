<?= $this->extend('layouts/seller') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Chat with <?= esc($peerName) ?></h5>
    <a href="<?= site_url('seller/messages') ?>" class="small">Inbox</a>
</div>
<div class="card border-0 shadow-sm rounded-4 p-3 mb-3" style="min-height: 320px;">
    <?php $me = (int) session()->get('user.id'); ?>
    <?php if (empty($messages)): ?>
        <p class="text-muted small mb-0">No messages yet.</p>
    <?php else: ?>
        <?php foreach ($messages as $m): ?>
            <div class="mb-2 <?= (int) $m['sender_id'] === $me ? 'text-end' : '' ?>">
                <div class="d-inline-block px-3 py-2 rounded-3 <?= (int) $m['sender_id'] === $me ? 'bg-primary text-white' : 'bg-light' ?>" style="max-width: 80%;">
                    <?= nl2br(esc($m['body'])) ?>
                </div>
                <div class="small text-muted"><?= date('d M H:i', strtotime($m['created_at'])) ?></div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<form action="<?= site_url('seller/messages/' . $conversation['id'] . '/send') ?>" method="POST" class="input-group">
    <?= csrf_field() ?>
    <input type="text" name="body" class="form-control" placeholder="Reply to buyer..." required>
    <button class="btn btn-primary" type="submit">Send</button>
</form>
<?= $this->endSection() ?>
