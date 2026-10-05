<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container py-4">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
                <h1 class="h3 fw-bold mb-2">Seller policies</h1>
                <p class="text-muted">Rules for listing, dispatch, and customer care on Solqam Marketplace.</p>
                <h5 class="fw-bold">Listings</h5>
                <ul class="text-secondary">
                    <li>Products must be genuine, accurately described, and legally sellable in Pakistan.</li>
                    <li>Prices are in PKR. Stock and variants must match what you can ship.</li>
                    <li>Prohibited: counterfeit goods, weapons, illegal medicines, and misleading ads.</li>
                </ul>
                <h5 class="fw-bold">Orders</h5>
                <ul class="text-secondary">
                    <li>Confirm orders promptly. Update your package to shipped with courier + tracking when you dispatch.</li>
                    <li>You fulfill only your items when a cart has multiple sellers.</li>
                    <li>Mark delivered only after the buyer has received the parcel.</li>
                </ul>
                <h5 class="fw-bold">Returns</h5>
                <ul class="text-secondary">
                    <li>Delivered orders may be returned if the buyer requests it and admin approves.</li>
                    <li>Approved refunds go to the buyer wallet ledger.</li>
                </ul>
                <h5 class="fw-bold">Account</h5>
                <ul class="text-secondary mb-0">
                    <li>Admin may suspend stores that break these policies or receive repeated complaints.</li>
                    <li>Online payments auto-split: commission to Solqam, net to your wallet. COD: you collect cash and Solqam’s commission is auto-credited to admin when you mark delivered.</li>
                </ul>
            </div>
        </div>
        <div class="col-lg-4"><?= $this->include('customer/pages/_nav') ?></div>
    </div>
</div>
<?= $this->endSection() ?>
