<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white text-center">
                <h4 class="fw-bold mb-2">Complete payment</h4>
                <p class="text-secondary">Order <strong><?= esc($orderNo) ?></strong> is <strong>pending</strong> until PayFast confirms.</p>

                <?php if (empty($pay['configured'])): ?>
                    <div class="alert alert-warning text-start">
                        Merchant API keys are not in <code>.env</code> yet. Add PayFast merchant ID + secured key, then open this page again. Paid tabhi jab PayFast success/IPN aaye.
                    </div>
                    <a class="btn btn-solqam rounded-pill" href="<?= site_url('account/orders/' . $orderId) ?>">View order</a>
                <?php else: ?>
                    <p class="small text-muted">Redirecting to the payment page…</p>
                    <form id="gwForm" method="<?= esc($pay['http_method'] ?? 'POST') ?>" action="<?= esc($pay['redirect_url']) ?>">
                        <?php foreach (($pay['fields'] ?? []) as $name => $value): ?>
                            <input type="hidden" name="<?= esc($name) ?>" value="<?= esc($value) ?>">
                        <?php endforeach; ?>
                        <button class="btn btn-solqam rounded-pill px-4" type="submit">Pay now</button>
                    </form>
                    <script>document.getElementById('gwForm').submit();</script>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
