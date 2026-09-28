<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container py-4" style="max-width: 760px;">
    <a href="<?= site_url('help') ?>" class="small d-inline-block mb-2">Back to Help Center</a>
    <h4 class="fw-bold"><?= esc($ticket['subject']) ?></h4>
    <span class="badge bg-light text-dark mb-3"><?= esc($ticket['status']) ?></span>
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-3">
        <div class="small text-muted mb-1">You · <?= date('d M Y H:i', strtotime($ticket['created_at'])) ?></div>
        <p class="mb-0"><?= nl2br(esc($ticket['message'])) ?></p>
    </div>
    <?php foreach ($replies as $r): ?>
        <div class="card border-0 shadow-sm rounded-4 p-3 mb-2 <?= ($r['author_role'] ?? '') === 'admin' ? 'bg-light' : '' ?>">
            <div class="small text-muted mb-1"><?= esc($r['author_name'] ?? 'User') ?> · <?= date('d M Y H:i', strtotime($r['created_at'])) ?></div>
            <p class="mb-0"><?= nl2br(esc($r['message'])) ?></p>
        </div>
    <?php endforeach; ?>
    <?php if ($ticket['status'] !== 'closed'): ?>
        <form action="<?= site_url('help/tickets/' . $ticket['id'] . '/reply') ?>" method="POST" class="mt-3">
            <?= csrf_field() ?>
            <textarea name="message" class="form-control mb-2" rows="3" required placeholder="Reply..."></textarea>
            <button class="btn btn-solqam rounded-pill px-4" type="submit">Send reply</button>
        </form>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
