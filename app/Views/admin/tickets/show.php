<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<a href="<?= site_url('admin/tickets') ?>" class="small">All tickets</a>
<h4 class="fw-bold mt-2"><?= esc($ticket['subject']) ?></h4>
<p class="small text-muted"><?= esc($ticket['customer_name'] ?? '') ?> · <?= esc($ticket['customer_email'] ?? '') ?> · <?= esc($ticket['status']) ?></p>
<div class="card border-0 shadow-sm rounded-4 p-3 mb-3">
    <?= nl2br(esc($ticket['message'])) ?>
</div>
<?php foreach ($replies as $r): ?>
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-2">
        <div class="small text-muted mb-1"><?= esc($r['author_name'] ?? '') ?> (<?= esc($r['author_role'] ?? '') ?>)</div>
        <?= nl2br(esc($r['message'])) ?>
    </div>
<?php endforeach; ?>
<form action="<?= site_url('admin/tickets/' . $ticket['id'] . '/reply') ?>" method="POST" class="card border-0 shadow-sm rounded-4 p-3 mt-3">
    <?= csrf_field() ?>
    <textarea name="message" class="form-control mb-2" rows="4" required></textarea>
    <div class="d-flex gap-2 align-items-center">
        <select name="status" class="form-select" style="max-width: 180px;">
            <option value="replied">Mark replied</option>
            <option value="closed">Close ticket</option>
            <option value="open">Keep open</option>
        </select>
        <button class="btn btn-danger rounded-pill px-4" type="submit">Send reply</button>
    </div>
</form>
<?= $this->endSection() ?>
