<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container py-4">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
                <h1 class="h3 fw-bold mb-2">Commission structure</h1>
                <p class="text-muted">Solqam charges a platform fee on seller items. The rate follows the product’s category. Admin-store products have no commission.</p>
                <div class="table-responsive mb-4">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th class="text-end">Seller commission</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (($categories ?? []) as $cat): ?>
                                <tr>
                                    <td><?= esc($cat['path_label'] ?? $cat['name']) ?></td>
                                    <td class="text-end fw-bold"><?= number_format((float) ($cat['effective_commission'] ?? 10), 1) ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="small text-muted">Items with no category use a <?= number_format((float) $percentage, 1) ?>% fallback.</p>
                <h5 class="fw-bold">How it works</h5>
                <ul class="text-secondary">
                    <li>Commission is charged only on product value (by category). Delivery charges are never included in commission.</li>
                    <li>Online pay: money is received by Solqam first, category commission is kept, goods remainder is credited to the seller. If the seller delivered, Solqam also passes the delivery fee to the seller. If Solqam delivered, Solqam keeps the delivery fee.</li>
                    <li>COD + seller delivery: seller collects cash; category commission is due to Solqam; delivery fee stays with the seller.</li>
                    <li>COD + Solqam delivery: Solqam collects cash, keeps delivery fee and commission, pays the seller product value minus commission.</li>
                    <li>If admin sells their own listing: no commission is cut.</li>
                    <li>Buyers receive the cashback % set on each product. Prepaid: instantly. COD / Pay later: after delivery.</li>
                </ul>
                <a href="<?= site_url('register?role=seller') ?>" class="btn btn-solqam rounded-pill px-4 mt-4">Open a seller account</a>
            </div>
        </div>
        <div class="col-lg-4"><?= $this->include('customer/pages/_nav') ?></div>
    </div>
</div>
<?= $this->endSection() ?>
