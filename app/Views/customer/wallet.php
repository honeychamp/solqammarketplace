<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h3 class="fw-bold mb-1" style="color: #0F172A;"><i class="bi bi-wallet2 text-warning me-2"></i> Solqam Cash Wallet</h3>
            <p class="text-secondary small mb-0">Checkout pe wallet pehle cut hota hai. Abhi baqi Cash on Delivery. PayFast keys ke baad JazzCash / EasyPaisa / card usi page se.</p>
        </div>
        <a href="<?= site_url('shop') ?>" class="btn btn-sol-primary btn-sm rounded-pill px-3 shadow-sm">
            <i class="bi bi-bag-check me-1"></i> Spend Balance at Checkout
        </a>
    </div>

    <div class="row g-4 mb-4">
        <!-- Balance Card -->
        <div class="col-md-5">
            <div class="card border-0 shadow rounded-4 p-4 text-white position-relative overflow-hidden" style="background: linear-gradient(135deg, #0B30E6 0%, #1D4ED8 100%);">
                <div class="position-absolute end-0 bottom-0 opacity-10 pe-3 pb-2">
                    <i class="bi bi-wallet2" style="font-size: 8rem; line-height: 1;"></i>
                </div>
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="text-uppercase small opacity-75 fw-bold" style="letter-spacing: 1px; font-size: 0.75rem;">Verified Available Balance</span>
                    <span class="badge bg-white bg-opacity-25 rounded-pill px-2 py-1 small">
                        <i class="bi bi-shield-check me-1"></i> Audit Protected
                    </span>
                </div>
                <h1 class="display-5 fw-bold mb-2 font-monospace">Rs. <?= number_format($balance, 2) ?></h1>
                <div class="small opacity-75 mb-3">PKR &bull; Zero static balance column &bull; Dynamic ledger sum</div>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge bg-white rounded-pill px-3 py-1.5 small fw-bold" style="color: var(--sol-primary);">
                        <i class="bi bi-lightning-charge-fill me-1 text-warning"></i> Auto-Apply at Checkout
                    </span>
                    <span class="badge bg-black bg-opacity-25 px-3 py-1.5 rounded-pill small">
                        Product cashback
                    </span>
                </div>
            </div>
        </div>

        <!-- How it works card -->
        <div class="col-md-7">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                <h5 class="fw-bold mb-3" style="color: #0F172A;"><i class="bi bi-info-circle text-primary me-2"></i> How Solqam Cash Ledger Works</h5>
                <ul class="list-unstyled mb-0">
                    <li class="d-flex mb-3">
                        <i class="bi bi-check-circle-fill text-success fs-5 me-3 flex-shrink-0"></i>
                        <div>
                            <strong>Cashback auto-credit:</strong> JazzCash / EasyPaisa / card / full wallet pay → the cashback % on each product hits your ledger instantly. COD / Pay later → after delivery.
                        </div>
                    </li>
                    <li class="d-flex mb-3">
                        <i class="bi bi-check-circle-fill text-success fs-5 me-3 flex-shrink-0"></i>
                        <div>
                            <strong>Instant Return Refunds:</strong> When a return request is approved by the vendor/admin, your refund is credited straight into your wallet ledger without bank processing delays.
                        </div>
                    </li>
                    <li class="d-flex mb-3">
                        <i class="bi bi-check-circle-fill text-success fs-5 me-3 flex-shrink-0"></i>
                        <div>
                            <strong>Pay at checkout:</strong> If wallet is more than the bill, only the bill is deducted and leftover stays in wallet. If wallet is less, remaining is JazzCash / EasyPaisa / card or Pay later (CNIC + bill copies).
                        </div>
                    </li>
                    <li class="d-flex">
                        <i class="bi bi-check-circle-fill text-success fs-5 me-3 flex-shrink-0"></i>
                        <div>
                            <strong>Append-Only Integrity:</strong> Entries are immutable. Current balance is computed mathematically via <code>SUM(CASE WHEN credit THEN amount ELSE -amount END)</code>.
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Ledger Transaction History -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
        <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-3">
            <h5 class="fw-bold mb-0" style="color: #0F172A;">Immutable Ledger Transactions</h5>
            <span class="badge bg-light text-muted border px-2 py-1 small"><?= count($transactions) ?> Entries</span>
        </div>

        <?php if (empty($transactions)): ?>
            <div class="text-center py-5 text-secondary">
                <i class="bi bi-journal-x fs-1 text-muted mb-2 d-block"></i>
                <p class="mb-0">No ledger transactions recorded yet. Complete an order to earn cashback listed on the product.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle table-hover">
                    <thead class="table-light small text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                        <tr>
                            <th>Entry ID</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Reference</th>
                            <th>Date &amp; Time</th>
                            <th class="text-end">Amount (PKR)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $txn): ?>
                            <tr>
                                <td class="text-muted small font-monospace">#<?= $txn['id'] ?></td>
                                <td>
                                    <?php if ($txn['type'] === 'credit'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill fw-semibold">
                                            <i class="bi bi-arrow-down-left me-1"></i> CREDIT
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 rounded-pill fw-semibold">
                                            <i class="bi bi-arrow-up-right me-1"></i> DEBIT
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-semibold text-dark"><?= esc($txn['description']) ?></td>
                                <td><span class="badge bg-light text-dark border font-monospace"><?= esc($txn['reference_type']) ?></span></td>
                                <td class="small text-muted"><?= date('d M Y, h:i A', strtotime($txn['created_at'])) ?></td>
                                <td class="text-end fw-bold font-monospace fs-6 <?= ($txn['type'] === 'credit') ? 'text-success' : 'text-danger' ?>">
                                    <?= ($txn['type'] === 'credit') ? '+' : '-' ?> Rs. <?= number_format($txn['amount'], 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
