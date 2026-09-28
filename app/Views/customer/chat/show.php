<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container py-4" style="max-width: 760px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0">Chat with <?= esc($peerName) ?></h5>
        <a href="<?= site_url('messages') ?>" class="small">All chats</a>
    </div>
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-3" style="min-height: 320px;">
        <?php if (empty($messages)): ?>
            <p class="text-muted small mb-0">Say hello — ask about stock, warranty, or delivery.</p>
        <?php else: ?>
            <?php $me = (int) session()->get('user.id'); ?>
            <?php foreach ($messages as $m): ?>
                <div class="mb-2 <?= (int) $m['sender_id'] === $me ? 'text-end' : '' ?>">
                    <div class="d-inline-block px-3 py-2 rounded-3 <?= (int) $m['sender_id'] === $me ? 'bg-solqam text-white' : 'bg-light' ?>" style="max-width: 80%;">
                        <?= nl2br(esc($m['body'])) ?>
                    </div>
                    <div class="small text-muted"><?= date('d M H:i', strtotime($m['created_at'])) ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <form action="<?= site_url('messages/' . $conversation['id'] . '/send') ?>" method="POST" class="input-group">
        <?= csrf_field() ?>
        <input type="text" name="body" class="form-control" placeholder="Type a message..." required>
        <button class="btn btn-solqam" type="submit">Send</button>
    </form>
</div>
<?= $this->endSection() ?>
