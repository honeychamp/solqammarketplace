<?= $this->extend('layouts/dukandar') ?>
<?= $this->section('content') ?>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h5 class="fw-bold">Scan Solqam Card</h5>
            <p class="small text-muted mb-1">Camera-scan the QR, or type the dashed number (example 01-01-C-00000002). QR may also be a card link.</p>
            <?php $pos = $pos ?? []; ?>
            <p class="small mb-3">Today <?= (int) ($pos['scans_today'] ?? 0) ?> scans · min bill Rs. <?= number_format((float) ($pos['min_bill'] ?? 50), 0) ?></p>
            <?php if (! empty($pos['last_sale'])): ?>
                <div class="alert alert-light border rounded-4 py-2 small mb-3">Last sale <?= esc(merchant_receipt($pos['last_sale']['id'])) ?> · <?= esc(merchant_scan_pan($pos['last_sale'])) ?> · Rs. <?= number_format((float) $pos['last_sale']['bill_amount'], 0) ?></div>
            <?php endif; ?>
            <div id="qr-reader" class="rounded-4 overflow-hidden bg-dark mb-2" style="min-height:220px;"></div>
            <div id="qrHint" class="small text-muted mb-3">Allow camera to scan. Rear camera is preferred.</div>
            <form method="post" action="<?= merchant_url('scan/lookup') ?>">
                <?= csrf_field() ?>
                <label class="form-label small">Card number</label>
                <div class="input-group">
                    <input class="form-control font-monospace" name="card_code" id="cardCodeInput" value="<?= esc($code ?? '') ?>" placeholder="01-01-C-00000002" autocomplete="off" required>
                    <button class="btn btn-solqam" type="submit">Look up</button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <?php if (! empty($preview)): ?>
            <?php $c = $preview['customer']; $pc = $preview['card']; $ins = $preview['insights'] ?? []; ?>
            <div class="mb-3" style="max-width:360px;">
                <?= view('partials/solqam_credit_card', ['card' => $pc, 'interactive' => false, 'showBreak' => true, 'hideCvv' => true]) ?>
            </div>
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h5 class="fw-bold"><?= esc($c['name']) ?></h5>
                <p class="small text-muted mb-2">Phone <?= esc($c['phone']) ?> · **** <?= esc($pc['last4'] ?? '') ?> · <?= esc($ins['tier_label'] ?? 'Blue') ?></p>
                <div class="d-flex gap-3 mb-3">
                    <div><div class="small text-muted">Wallet</div><div class="fw-bold">Rs. <?= number_format((float) $preview['balance'], 0) ?></div></div>
                    <div><div class="small text-muted">Pending</div><div class="fw-bold text-warning">Rs. <?= number_format((float) $preview['pending'], 0) ?></div></div>
                    <div><div class="small text-muted">Today left</div><div class="fw-bold">Rs. <?= number_format((float) ($ins['today_left'] ?? 0), 0) ?></div></div>
                </div>
                <form method="post" action="<?= merchant_url('scan/sale') ?>" id="saleForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="card_code" value="<?= esc($pc['pan'] ?? $pc['card_code'] ?? '') ?>">
                    <label class="form-label">Shopping total (PKR)</label>
                    <input class="form-control form-control-lg font-monospace" type="number" min="50" step="1" name="bill_amount" id="billAmount" required>
                    <div class="d-flex flex-wrap gap-2 mt-2" id="billQuick">
                        <?php foreach ([500, 1000, 2000, 5000] as $q): ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" data-bill="<?= $q ?>">Rs. <?= number_format($q) ?></button>
                        <?php endforeach; ?>
                    </div>
                    <div class="small text-muted mt-2">Automatic <?= (int) ($percent ?? 5) ?>% credit: <strong id="creditPreview">Rs. 0</strong> — customer CVV is never shown on this screen.</div>
                    <div class="row g-2 mt-2">
                        <div class="col-md-6">
                            <label class="form-label small">CVV <?= (float) ($cvvAt ?? 5000) > 0 ? '(required if bill ≥ Rs. ' . number_format((float) ($cvvAt ?? 5000), 0) . ')' : '' ?></label>
                            <input class="form-control" name="cvv" inputmode="numeric" maxlength="3" placeholder="3 digits">
                        </div>
                        <?php if (! empty($pc['has_pin'])): ?>
                        <div class="col-md-6">
                            <label class="form-label small">Shop PIN (required)</label>
                            <input class="form-control" name="pin" inputmode="numeric" maxlength="4" placeholder="4 digits" required>
                        </div>
                        <?php endif; ?>
                    </div>
                    <button class="btn btn-solqam rounded-pill mt-3 px-4" type="submit">Authorize &amp; pending credit</button>
                </form>
                <?php if (! empty($preview['shop_history'])): ?>
                    <hr>
                    <div class="small fw-semibold mb-2">Previous visits here</div>
                    <?php foreach ($preview['shop_history'] as $h): ?>
                        <div class="small text-muted d-flex justify-content-between">
                            <span><?= esc(merchant_receipt($h['id'])) ?> · Rs. <?= number_format((float) $h['bill_amount'], 0) ?></span>
                            <span><?= merchant_status_badge((string) $h['status']) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center text-muted">Scan or enter a card to open the customer like a POS terminal.</div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/solqam-geo.js') ?>?v=20261110b"></script>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(function () {
  const input = document.getElementById('cardCodeInput');
  solqamFormatCardInput(input);
  const bill = document.getElementById('billAmount');
  const prev = document.getElementById('creditPreview');
  const pct = <?= json_encode((float) ($percent ?? 5)) ?>;
  const form = document.getElementById('saleForm');
  const paintCredit = function () {
    const n = parseFloat(bill && bill.value ? bill.value : '0') || 0;
    if (prev) prev.textContent = 'Rs. ' + Math.round(n * pct / 100).toLocaleString();
  };
  if (bill) bill.addEventListener('input', paintCredit);
  document.getElementById('billQuick')?.querySelectorAll('[data-bill]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (!bill) return;
      bill.value = btn.getAttribute('data-bill');
      paintCredit();
      bill.focus();
    });
  });
  if (form) {
    form.addEventListener('submit', function (e) {
      const n = parseFloat(bill && bill.value ? bill.value : '0') || 0;
      const credit = Math.round(n * pct / 100);
      if (n < 50) return;
      if (!confirm('Authorize bill Rs. ' + n.toLocaleString() + ' and create pending credit Rs. ' + credit.toLocaleString() + '?')) {
        e.preventDefault();
      }
    });
  }
  const hint = document.getElementById('qrHint');
  if (!window.Html5Qrcode || !document.getElementById('qr-reader')) return;
  const qr = new Html5Qrcode('qr-reader');
  Html5Qrcode.getCameras().then(function (cams) {
    if (!cams.length) {
      if (hint) hint.textContent = 'No camera found. Type the card number instead.';
      return;
    }
    qr.start(cams[cams.length - 1].id, { fps: 8, qrbox: 220 }, function (text) {
      if (input) input.value = text;
      qr.stop().catch(function () {});
      input.form && input.form.submit();
    });
  }).catch(function () {
    if (hint) hint.textContent = 'Camera blocked. Allow camera permission, or type the card number.';
  });
})();
</script>
<?= $this->endSection() ?>
