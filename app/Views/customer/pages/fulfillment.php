<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container py-4">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
                <h1 class="h3 fw-bold mb-2">Fulfillment</h1>
                <p class="text-muted">Each seller package is either delivered by the seller or handed to Solqam. The buyer still pays one shipping amount at checkout. Commission is never charged on delivery — only on product value, by category.</p>
                <h5 class="fw-bold">Seller delivers</h5>
                <ul class="text-secondary">
                    <li>Seller confirms, books their own courier, marks shipped / delivered.</li>
                    <li>COD: seller (or their courier) collects the cash. Category commission on products is due to Solqam. Delivery fee stays with the seller.</li>
                    <li>Online pay: money is with Solqam first. After delivery Solqam keeps category commission and passes the delivery fee to the seller with the goods payout.</li>
                </ul>
                <h5 class="fw-bold">Solqam delivers</h5>
                <ul class="text-secondary">
                    <li>Seller sends the parcel to Solqam. Admin inbound desk receives it, then ships to the buyer.</li>
                    <li>COD: cash is collected by Solqam. Solqam keeps the delivery fee and category commission, then pays the seller product value minus commission.</li>
                    <li>Online pay: Solqam already has the money. Same split — delivery fee stays with Solqam, commission from product value, rest to seller.</li>
                </ul>
                <p class="text-secondary mb-0">Buyers track from <a href="<?= site_url('track') ?>">Track My Order</a>. Multi-seller carts have one package per seller.</p>
            </div>
        </div>
        <div class="col-lg-4"><?= $this->include('customer/pages/_nav') ?></div>
    </div>
</div>
<?= $this->endSection() ?>
