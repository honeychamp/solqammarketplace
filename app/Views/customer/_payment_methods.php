<?php
$tone = $tone ?? 'light';
$chip = $tone === 'dark' ? 'payment-chip' : 'pay-method-chip';
?>
<div class="payment-badge-grid">
    <span class="<?= $chip ?>"><i class="bi bi-cash-coin"></i> Cash on Delivery</span>
    <span class="<?= $chip ?>"><i class="bi bi-phone"></i> JazzCash</span>
    <span class="<?= $chip ?>"><i class="bi bi-phone"></i> EasyPaisa</span>
    <span class="<?= $chip ?>"><i class="bi bi-credit-card-2-front"></i> Visa</span>
    <span class="<?= $chip ?>"><i class="bi bi-credit-card"></i> Mastercard</span>
    <span class="<?= $chip ?>"><i class="bi bi-bank"></i> UnionPay</span>
    <span class="<?= $chip ?>"><i class="bi bi-qr-code"></i> PayPak</span>
    <span class="<?= $chip ?>"><i class="bi bi-wallet2"></i> Solqam Wallet</span>
</div>
