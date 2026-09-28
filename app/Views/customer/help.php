<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container py-4">
    <h3 class="fw-bold mb-2">Help Center</h3>
    <p class="text-muted mb-4">Track orders, returns, payments, and open a ticket with Solqam support.</p>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <a href="<?= site_url('track') ?>" class="card border-0 shadow-sm rounded-4 p-4 text-decoration-none text-dark h-100">
                <i class="bi bi-truck fs-3 text-solqam"></i>
                <h6 class="fw-bold mt-2">Track an order</h6>
                <p class="small text-muted mb-0">Use your order number and phone.</p>
            </a>
        </div>
        <div class="col-md-4">
            <a href="<?= site_url('returns-policy') ?>" class="card border-0 shadow-sm rounded-4 p-4 text-decoration-none text-dark h-100">
                <i class="bi bi-arrow-counterclockwise fs-3 text-solqam"></i>
                <h6 class="fw-bold mt-2">Returns &amp; refunds</h6>
                <p class="small text-muted mb-0">How returns work and wallet refunds.</p>
            </a>
        </div>
        <div class="col-md-4">
            <a href="<?= site_url('account/wallet') ?>" class="card border-0 shadow-sm rounded-4 p-4 text-decoration-none text-dark h-100">
                <i class="bi bi-wallet2 fs-3 text-solqam"></i>
                <h6 class="fw-bold mt-2">Wallet cashback</h6>
                <p class="small text-muted mb-0">Cashback % is set on each product. Prepaid instantly; COD after delivery.</p>
            </a>
        </div>
    </div>

    <?php if (session()->get('user.id')): ?>
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h5 class="fw-bold mb-3">Open a support ticket</h5>
                    <form action="<?= site_url('help/tickets') ?>" method="POST">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Subject</label>
                            <input type="text" name="subject" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">How can we help?</label>
                            <textarea name="message" class="form-control" rows="4" required></textarea>
                        </div>
                        <button class="btn btn-solqam rounded-pill px-4" type="submit">Submit ticket</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h5 class="fw-bold mb-3">My tickets</h5>
                    <?php if (empty($tickets)): ?>
                        <p class="text-muted small mb-0">No tickets yet.</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($tickets as $t): ?>
                                <a href="<?= site_url('help/tickets/' . $t['id']) ?>" class="list-group-item list-group-item-action px-0">
                                    <div class="d-flex justify-content-between">
                                        <strong><?= esc($t['subject']) ?></strong>
                                        <span class="badge bg-light text-dark"><?= esc($t['status']) ?></span>
                                    </div>
                                    <small class="text-muted"><?= date('d M Y', strtotime($t['created_at'])) ?></small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-light border">Please <a href="<?= site_url('login') ?>">sign in</a> to open a support ticket.</div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
