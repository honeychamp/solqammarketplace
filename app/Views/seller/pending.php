<?= $this->extend('layouts/seller') ?>
<?= $this->section('content') ?>
<?php
$status = $approvalStatus ?? 'pending';
$isRejected = $status === 'rejected';
?>
<div class="command-panel p-4 p-lg-5">
    <div class="d-flex flex-wrap align-items-start gap-3 mb-4">
        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:56px;height:56px;background:<?= $isRejected ? 'rgba(240,20,47,.12)' : 'rgba(245,158,11,.18)' ?>;">
            <i class="bi <?= $isRejected ? 'bi-x-circle-fill text-danger' : 'bi-hourglass-split text-warning' ?> fs-3"></i>
        </div>
        <div>
            <div class="small text-uppercase fw-bold text-muted mb-1">Seller Hub</div>
            <h3 class="fw-black mb-2"><?= esc($storeName ?? 'Your store') ?></h3>
            <?php if ($isRejected): ?>
                <span class="badge bg-danger">Rejected</span>
            <?php else: ?>
                <span class="badge bg-warning text-dark">Pending admin approval</span>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($isRejected): ?>
        <p class="mb-3">Solqam did not approve this seller account. Products, orders, campaigns and payouts stay locked.</p>
        <?php if (!empty($rejectionReason)): ?>
            <div class="alert alert-danger rounded-4"><?= esc($rejectionReason) ?></div>
        <?php endif; ?>
        <p class="text-muted small mb-0">If you believe this is a mistake, email <a href="mailto:info@solqam.com">info@solqam.com</a>.</p>
    <?php else: ?>
        <p class="lead mb-3">You are signed in. An administrator must approve your store before you can handle any seller data.</p>
        <ul class="mb-4">
            <li>You cannot add or edit products</li>
            <li>You cannot process orders, chats, campaigns or payouts</li>
            <li>Your store will not appear on the marketplace until approval</li>
        </ul>
        <p class="text-muted small mb-0">Refresh this page after the admin team approves you. Then the full Seller Hub unlocks.</p>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
