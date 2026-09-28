<?= $this->extend('layouts/seller') ?>
<?= $this->section('content') ?>
<h4 class="fw-bold mb-4">Product Q&amp;A</h4>
<?php foreach ($questions as $q): ?>
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-3">
        <div class="small text-muted"><?= esc($q['product_name']) ?> · <?= esc($q['asker_name']) ?></div>
        <div class="fw-semibold mb-2"><?= esc($q['question']) ?></div>
        <?php if ($q['answer']): ?>
            <div class="bg-light rounded-3 p-2 small"><strong>Your answer:</strong> <?= esc($q['answer']) ?></div>
        <?php else: ?>
            <form action="<?= site_url('seller/questions/' . $q['id'] . '/answer') ?>" method="POST" class="d-flex gap-2">
                <?= csrf_field() ?>
                <input name="answer" class="form-control" placeholder="Reply to customer" required>
                <button class="btn btn-primary">Reply</button>
            </form>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
<?php if (empty($questions)): ?>
    <p class="text-muted">No questions yet.</p>
<?php endif; ?>
<?= $this->endSection() ?>
