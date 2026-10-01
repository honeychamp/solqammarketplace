<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container py-4">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
                <h1 class="h3 fw-bold mb-2">Shipping &amp; delivery</h1>
                <p class="text-muted">Admin sets a delivery charge for each city (Admin → Shipping). At checkout your address city is matched automatically and that rate is applied. Add a city named Default for any city you did not list. “Free above Rs.” only waives delivery when that amount is greater than 0 and the cart reaches it — 0 means delivery is never free.</p>
                <?php if (!empty($zones)): ?>
                <div class="table-responsive mb-4">
                    <table class="table">
                        <thead><tr><th>City</th><th>Delivery</th><th>Free above</th><th>ETA</th></tr></thead>
                        <tbody>
                        <?php foreach ($zones as $z): ?>
                            <tr>
                                <td><?= esc($z['city']) ?></td>
                                <td>Rs. <?= number_format((float) $z['rate'], 0) ?></td>
                                <td><?= (float) $z['free_above'] > 0 ? 'Rs. ' . number_format((float) $z['free_above'], 0) : 'No free delivery' ?></td>
                                <td><?= esc($z['eta_days']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                <h5 class="fw-bold">What to expect</h5>
                <ul class="text-secondary">
                    <li>Sellers dispatch themselves using TCS, Leopard, Trax, Pakistan Post, or a local rider.</li>
                    <li>ETA depends on origin and destination (often 1–5 days after dispatch).</li>
                    <li>Cash on Delivery is available. JazzCash live checkout waits for merchant keys.</li>
                </ul>
                <h5 class="fw-bold">Track a parcel</h5>
                <p class="text-secondary mb-0">Use <a href="<?= site_url('track') ?>">Track My Order</a> with your Solqam order number. Seller tracking IDs show on the order once shipped.</p>
                <a href="<?= site_url('track') ?>" class="btn btn-solqam rounded-pill px-4 mt-4">Track an order</a>
            </div>
        </div>
        <div class="col-lg-4"><?= $this->include('customer/pages/_nav') ?></div>
    </div>
</div>
<?= $this->endSection() ?>
