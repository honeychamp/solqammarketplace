<?php
/** @var array $order */
$showPayLaterDocs = ! empty($showPayLaterDocs);
$method = (string) ($order['payment_method'] ?? 'cod');
$walletUsed = (float) ($order['wallet_amount_used'] ?? 0);
$payable = (float) ($order['final_payable'] ?? 0);
$payLabel = payment_method_label($method);
if ($walletUsed > 0.009 && $payable <= 0.009) {
    $payLabel = 'Wallet (full order)';
} elseif ($walletUsed > 0.009) {
    $payLabel .= ' + wallet';
}
$payStatus = strtoupper((string) ($order['payment_status'] ?? 'PENDING'));
$giftTotal = (float) ($order['gift_wrap_amount'] ?? 0);
?>
<div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
    <h5 class="fw-bold mb-3 border-bottom pb-2"><i class="bi bi-person-lines-fill me-1"></i> Customer</h5>
    <div class="small">
        <div class="text-muted text-uppercase" style="font-size:10px;letter-spacing:.06em;">Account</div>
        <strong class="d-block text-dark"><?= esc($order['customer_name'] ?? 'Guest') ?></strong>
        <?php if (! empty($order['customer_phone'])): ?>
            <div><i class="bi bi-telephone me-1"></i><?= esc($order['customer_phone']) ?></div>
        <?php endif; ?>
        <?php if (! empty($order['customer_email'])): ?>
            <div><i class="bi bi-envelope me-1"></i><?= esc($order['customer_email']) ?></div>
        <?php endif; ?>
        <hr class="my-3">
        <div class="text-muted text-uppercase" style="font-size:10px;letter-spacing:.06em;">Ship to</div>
        <strong class="d-block text-dark"><?= esc($order['recipient_name'] ?? '') ?></strong>
        <div><?= esc($order['street_address'] ?? '') ?></div>
        <?php if (! empty($order['area'])): ?>
            <div>Area: <?= esc($order['area']) ?></div>
        <?php endif; ?>
        <?php if (! empty($order['landmark'])): ?>
            <div>Landmark: <?= esc($order['landmark']) ?></div>
        <?php endif; ?>
        <div><?= esc($order['city'] ?? '') ?><?= ! empty($order['province']) ? ', ' . esc($order['province']) : '' ?> <?= esc($order['postal_code'] ?? '') ?></div>
        <div class="mt-1"><i class="bi bi-telephone me-1"></i><?= esc($order['recipient_phone'] ?? '') ?></div>
        <?php if (! empty($order['alternate_phone'])): ?>
            <div>WhatsApp / alt: <?= esc($order['alternate_phone']) ?></div>
        <?php endif; ?>
        <?php if (! empty($order['notes'])): ?>
            <div class="alert alert-light border mt-3 mb-0 small"><strong>Buyer note:</strong> <?= esc($order['notes']) ?></div>
        <?php endif; ?>
        <?= $buyer360 ?? '' ?>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
    <h6 class="fw-bold mb-3 border-bottom pb-2">Payment</h6>
    <div class="small">
        <div class="mb-1"><strong>Method:</strong> <?= esc($payLabel) ?></div>
        <div class="mb-1"><strong>Status:</strong>
            <span class="badge bg-<?= ($payStatus === 'PAID') ? 'success' : 'warning text-dark' ?>"><?= esc($payStatus) ?></span>
        </div>
        <?php if (! empty($order['transaction_ref'])): ?>
            <div class="mb-1">Ref: <code><?= esc($order['transaction_ref']) ?></code></div>
        <?php endif; ?>
        <?php if (! empty($order['paid_at'])): ?>
            <div class="mb-1 text-muted">Paid at: <?= esc(pk_datetime($order['paid_at'])) ?> PKT</div>
        <?php endif; ?>
        <?php if ($giftTotal > 0): ?>
            <div class="mb-1"><strong>Gift wrap total:</strong> Rs. <?= number_format($giftTotal, 0) ?></div>
        <?php endif; ?>
        <?php if ((float) ($order['shipping_amount'] ?? 0) > 0): ?>
            <div class="mb-1">Delivery: Rs. <?= number_format((float) $order['shipping_amount'], 0) ?></div>
        <?php endif; ?>
        <?php if ($walletUsed > 0): ?>
            <div class="mb-1">Wallet used: Rs. <?= number_format($walletUsed, 0) ?></div>
        <?php endif; ?>
        <?php if ((float) ($order['coupon_discount'] ?? 0) > 0): ?>
            <div class="mb-1">Coupon<?= ! empty($order['coupon_code']) ? ' (' . esc($order['coupon_code']) . ')' : '' ?>: − Rs. <?= number_format((float) $order['coupon_discount'], 0) ?></div>
        <?php endif; ?>
        <div class="mb-1"><strong>Buyer pays now:</strong> Rs. <?= number_format($payable, 0) ?></div>
        <?php if ((float) ($order['pay_later_wallet'] ?? 0) > 0): ?>
            <div class="mb-1">Pay later on wallet: Rs. <?= number_format((float) $order['pay_later_wallet'], 0) ?><?= ! empty($order['pay_later_cleared']) ? ' (cleared)' : '' ?></div>
        <?php endif; ?>
        <?php if ($showPayLaterDocs && ! empty($order['pay_later'])): ?>
            <hr>
            <div class="fw-bold mb-1">Pay later documents</div>
            <div><?= esc($order['pay_later']['full_name'] ?? '') ?></div>
            <div>CNIC: <?= esc($order['pay_later']['cnic_number'] ?? '') ?></div>
            <div>Phone: <?= esc($order['pay_later']['phone'] ?? '') ?></div>
            <div class="mb-2"><?= esc($order['pay_later']['address_text'] ?? '') ?></div>
            <div class="d-flex flex-column gap-1">
                <?php if (! empty($order['pay_later']['cnic_front_path'])): ?>
                    <a href="<?= esc($order['pay_later']['cnic_front_path']) ?>" target="_blank">CNIC front</a>
                <?php endif; ?>
                <?php if (! empty($order['pay_later']['cnic_back_path'])): ?>
                    <a href="<?= esc($order['pay_later']['cnic_back_path']) ?>" target="_blank">CNIC back</a>
                <?php endif; ?>
                <?php if (! empty($order['pay_later']['utility_bill_path'])): ?>
                    <a href="<?= esc($order['pay_later']['utility_bill_path']) ?>" target="_blank">Utility bill</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <?= $payFootnote ?? '' ?>
    </div>
</div>
