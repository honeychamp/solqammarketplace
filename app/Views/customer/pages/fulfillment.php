<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container py-4">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
                <h1 class="h3 fw-bold mb-2">Fulfillment by Solqam</h1>
                <p class="text-muted">Solqam does not run a 3PL warehouse yet. Sellers pack and hand parcels to their own courier (TCS, Leopard, Trax, Pakistan Post, or local rider).</p>
                <h5 class="fw-bold">Seller steps</h5>
                <ol class="text-secondary">
                    <li>Confirm the order in Seller Hub.</li>
                    <li>Pack the item, book your courier, enter courier name + tracking number.</li>
                    <li>Mark shipped, then delivered when the buyer receives it.</li>
                </ol>
                <h5 class="fw-bold">Buyer tracking</h5>
                <p class="text-secondary">Buyers track from <a href="<?= site_url('track') ?>">Track My Order</a> using the order number. Multi-seller carts show a package per seller.</p>
                <h5 class="fw-bold">What Solqam provides</h5>
                <ul class="text-secondary mb-0">
                    <li>Order, wallet, commission, and payout records.</li>
                    <li>Help tickets if a shipment is stuck.</li>
                    <li>No live courier API — tracking numbers are entered by the seller.</li>
                </ul>
            </div>
        </div>
        <div class="col-lg-4"><?= $this->include('customer/pages/_nav') ?></div>
    </div>
</div>
<?= $this->endSection() ?>
