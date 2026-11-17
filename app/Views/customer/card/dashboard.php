<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$card = $card ?? [];
$balance = (float) ($balance ?? 0);
$pending = (float) ($pending ?? 0);
$ins = $insights ?? [];
$frozen = ! empty($card['is_frozen']);
$parts = $card['id_parts'] ?? [];
?>
<link rel="stylesheet" href="<?= base_url('assets/css/solqam-card.css') ?>?v=20261113f">
<div class="container py-4">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success rounded-4"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger rounded-4"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>

    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
        <div>
            <div class="small text-uppercase fw-bold text-muted" style="letter-spacing:.14em;">Solqam Marketplace Card</div>
            <h3 class="fw-bold mb-1">Your Solqam Card</h3>
            <p class="text-secondary small mb-0">QR is on the card. Number is division + city of division + member type + unique ID. Show it at any partner merchant.</p>
        </div>
        <span class="badge rounded-pill px-3 py-2" style="background:#0B30E6;"><?= esc($ins['tier_label'] ?? 'Blue') ?> member</span>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="sq-print-area">
                <?= view('partials/solqam_credit_card', ['card' => $card, 'interactive' => true]) ?>
            </div>
            <div class="d-flex flex-wrap gap-2 justify-content-center mt-3">
                <button class="btn btn-sm btn-dark rounded-pill" type="button" id="sqCopyBtn">Copy card number</button>
                <button class="btn btn-sm btn-outline-dark rounded-pill" type="button" onclick="window.print()">Print card</button>
                <span class="small text-muted align-self-center">Tap card for CVV</span>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="row g-3 mb-3">
                <div class="col-6"><div class="sq-stat"><div class="k">Available</div><div class="v">Rs. <?= number_format($balance, 0) ?></div></div></div>
                <div class="col-6"><div class="sq-stat"><div class="k">Pending</div><div class="v text-warning">Rs. <?= number_format($pending, 0) ?></div></div></div>
                <div class="col-6"><div class="sq-stat"><div class="k">This month</div><div class="v">Rs. <?= number_format((float) ($ins['month_earned'] ?? 0), 0) ?></div></div></div>
                <div class="col-6"><div class="sq-stat"><div class="k">Lifetime earned</div><div class="v">Rs. <?= number_format((float) ($ins['lifetime_earned'] ?? 0), 0) ?></div></div></div>
                <div class="col-6"><div class="sq-stat"><div class="k">Today left</div><div class="v">Rs. <?= number_format((float) ($ins['today_left'] ?? 0), 0) ?></div></div></div>
                <div class="col-6"><div class="sq-stat"><div class="k">Shop visits</div><div class="v"><?= (int) ($ins['visits'] ?? 0) ?></div></div></div>
            </div>
            <div class="row g-2 mb-3">
                <div class="col-6">
                    <form method="post" action="<?= site_url('card/freeze') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="frozen" value="<?= $frozen ? '0' : '1' ?>">
                        <button class="sq-ctrl-btn" type="submit">
                            <i class="bi bi-snow me-1"></i>
                            <strong><?= $frozen ? 'Unfreeze card' : 'Freeze card' ?></strong>
                            <div class="small text-muted"><?= $frozen ? 'Shops can scan again' : 'Block scans instantly' ?></div>
                        </button>
                    </form>
                </div>
                <div class="col-6">
                    <form method="post" action="<?= site_url('card/cvv') ?>" onsubmit="return confirm('Issue a new CVV? Old code stops working.');">
                        <?= csrf_field() ?>
                        <button class="sq-ctrl-btn" type="submit">
                            <i class="bi bi-shield-lock me-1"></i>
                            <strong>New CVV</strong>
                            <div class="small text-muted">Rotate security code</div>
                        </button>
                    </form>
                </div>
            </div>
            <form class="card border-0 shadow-sm rounded-4 p-3" method="post" action="<?= site_url('card/pin') ?>">
                <?= csrf_field() ?>
                <div class="fw-bold mb-1">Shop PIN</div>
                <p class="small text-muted mb-2"><?= ! empty($card['has_pin']) ? 'PIN is on. The merchant must enter it after scan.' : 'Set a 4-digit PIN so only you can approve shop scans.' ?></p>
                <div class="input-group">
                    <input class="form-control" name="pin" inputmode="numeric" maxlength="4" pattern="\d{4}" placeholder="••••" required>
                    <button class="btn btn-solqam" type="submit">Save PIN</button>
                </div>
            </form>
            <?php $share = (string) ($share_url ?? $card['share_url'] ?? ''); ?>
            <?php if ($share !== ''): ?>
            <div class="card border-0 shadow-sm rounded-4 p-3 mt-3">
                <div class="fw-bold mb-1">Share / scan link</div>
                <p class="small text-muted mb-2">Partner shops can scan this QR or open this link in Merchant Hub.</p>
                <div class="input-group input-group-sm">
                    <input class="form-control font-monospace" id="sqShareUrl" readonly value="<?= esc($share) ?>">
                    <button class="btn btn-outline-dark" type="button" id="sqShareBtn">Copy link</button>
                </div>
            </div>
            <?php endif; ?>
            <a class="btn btn-outline-dark rounded-pill mt-3" href="<?= site_url('account/wallet') ?>">Full wallet ledger</a>
        </div>
    </div>

    <h6 class="fw-bold mb-3">Pay with Solqam</h6>
    <div class="row g-3 mb-4">
        <?php foreach (($services ?? []) as $svc): ?>
            <div class="col-6 col-md-4 col-lg">
                <?php $href = ($svc['url'] ?? '') !== '' ? $svc['url'] : site_url('card/shops'); ?>
                <a href="<?= $href ?>" class="sq-service-tile">
                    <i class="bi <?= esc($svc['icon']) ?> fs-3 text-solqam"></i>
                    <div class="fw-bold mt-1"><?= esc($svc['name']) ?></div>
                    <div class="small text-muted"><?= esc($svc['blurb']) ?></div>
                    <?php if (($svc['url'] ?? '') === ''): ?><div class="soon mt-1">Network expanding</div><?php endif; ?>
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (! empty($visits_history)): ?>
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
        <h6 class="fw-bold">Recent shop visits</h6>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead><tr><th>Merchant</th><th>Bill</th><th>5%</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($visits_history as $h): ?>
                    <tr>
                        <td><?= esc($h['shop_name'] ?? ('Sale #' . $h['id'])) ?><div class="small text-muted"><?= esc($h['shop_city'] ?? '') ?></div></td>
                        <td>Rs. <?= number_format((float) $h['bill_amount'], 0) ?></td>
                        <td>Rs. <?= number_format((float) $h['credit_amount'], 0) ?></td>
                        <td><?= merchant_status_badge((string) ($h['status'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php if (! empty($holds)): ?>
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
        <h6 class="fw-bold">Authorizations pending</h6>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead><tr><th>Merchant</th><th>Bill</th><th>Credit</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($holds as $h): ?>
                    <tr>
                        <td><?= esc($h['shop_name'] ?? ('Sale #' . $h['id'])) ?><div class="small text-muted"><?= esc($h['shop_city'] ?? '') ?></div></td>
                        <td>Rs. <?= number_format((float) $h['bill_amount'], 0) ?></td>
                        <td class="text-warning fw-bold">Rs. <?= number_format((float) $h['credit_amount'], 0) ?></td>
                        <td><?= $h['status'] === 'processing' ? 'Merchant paid — releasing' : 'Awaiting merchant payment' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="fw-bold mb-0">Merchants that accept this card</h6>
        <a href="<?= site_url('card/shops') ?>">Find nearby</a>
    </div>
    <div class="row g-3 mb-4">
        <?php foreach (array_slice($shops ?? [], 0, 6) as $shop): ?>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                    <div class="fw-bold"><?= esc($shop['shop_name']) ?></div>
                    <div class="small text-muted"><?= esc($shop['category']) ?> · <?= esc($shop['city']) ?> <?= esc($shop['area'] ?? '') ?></div>
                    <div class="small"><?= esc($shop['address'] ?? '') ?></div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($shops)): ?>
            <p class="text-muted">Partner shops appear after Solqam approves merchants in your city.</p>
        <?php endif; ?>
    </div>

    <div class="card border-0 shadow-sm rounded-4 p-3">
        <h6 class="fw-bold">Card statement</h6>
        <?php if (empty($ledger)): ?>
            <p class="text-muted small mb-0">No posted transactions yet. Shop credits post after admin release.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead><tr><th>Posted</th><th>Description</th><th class="text-end">Amount</th></tr></thead>
                    <tbody>
                    <?php foreach ($ledger as $txn): ?>
                        <tr>
                            <td class="small text-muted"><?= esc($txn['created_at'] ?? '') ?></td>
                            <td><?= esc($txn['description']) ?></td>
                            <td class="text-end fw-bold <?= $txn['type'] === 'credit' ? 'text-success' : 'text-danger' ?>"><?= $txn['type'] === 'credit' ? '+' : '−' ?> Rs. <?= number_format((float) $txn['amount'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
(function () {
  const flip = document.getElementById('sqCardFlip');
  if (flip) {
    const tog = () => flip.classList.toggle('is-flipped');
    flip.addEventListener('click', tog);
    flip.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); tog(); } });
  }
  const id = <?= json_encode($card['pan_grouped'] ?? $card['id_parts']['pan'] ?? $card['pan'] ?? '') ?>;
  document.getElementById('sqCopyBtn')?.addEventListener('click', function () {
    if (!id) return;
    navigator.clipboard.writeText(id).then(() => { this.textContent = 'Copied'; });
  });
  document.getElementById('sqShareBtn')?.addEventListener('click', function () {
    const url = document.getElementById('sqShareUrl')?.value || '';
    if (!url) return;
    navigator.clipboard.writeText(url).then(() => { this.textContent = 'Copied'; });
  });
})();
</script>
<?= $this->endSection() ?>
