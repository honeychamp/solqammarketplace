<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container py-4">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
                <h1 class="h3 fw-bold mb-2">Commission structure</h1>
                <p class="text-muted">Solqam charges a platform fee on each sold item. The live rate is set by admin and applied to new orders.</p>
                <div class="p-4 rounded-4 mb-4 text-white" style="background: linear-gradient(135deg,#0B30E6,#0722ab);">
                    <div class="small text-white-50 text-uppercase fw-bold">Current platform commission</div>
                    <div class="display-5 fw-black mb-0"><?= number_format($percentage, 1) ?>%</div>
                    <div class="small">of item subtotal (before shipping)</div>
                </div>
                <h5 class="fw-bold">How it works</h5>
                <ul class="text-secondary">
                    <li>Online pay (JazzCash / EasyPaisa / card / wallet): money is received by Solqam, commission is kept automatically, remaining amount is credited to the seller wallet with no extra click.</li>
                    <li>COD: order is placed with the seller. When the seller marks delivered, they keep the cash and Solqam’s commission is auto-credited to the admin wallet.</li>
                    <li>If admin sells their own listing: no commission is cut. Payment stays with the admin store.</li>
                    <li>Buyers receive the cashback % set on each product. Prepaid: instantly. COD / Pay later: after delivery.</li>
                </ul>
                <h5 class="fw-bold">Example</h5>
                <p class="text-secondary mb-0">Rs. 2,000 item × <?= number_format($percentage, 1) ?>% commission = Rs. <?= number_format(2000 * $percentage / 100, 0) ?>. Seller net also minus that product’s cashback %.</p>
                <a href="<?= site_url('register?role=seller') ?>" class="btn btn-solqam rounded-pill px-4 mt-4">Open a seller account</a>
            </div>
        </div>
        <div class="col-lg-4"><?= $this->include('customer/pages/_nav') ?></div>
    </div>
</div>
<?= $this->endSection() ?>
