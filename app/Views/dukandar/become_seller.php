<?= $this->extend('layouts/dukandar') ?>
<?= $this->section('content') ?>
<div class="col-lg-7">
    <div class="card border-0 shadow-sm rounded-4 p-4">
        <h5 class="fw-bold">Also sell on Solqam Marketplace</h5>
        <p class="text-secondary">This creates a marketplace seller store on the same login. You keep Merchant Hub for in-shop Solqam Card scans. Product listing starts after Solqam approves the online store.</p>
        <p class="small mb-4">Store name will start as <strong><?= esc($shop['shop_name'] ?? 'your shop') ?></strong>. You can change it later in Seller Hub.</p>
        <form method="post" action="<?= merchant_url('become-seller') ?>">
            <?= csrf_field() ?>
            <button class="btn btn-solqam rounded-pill px-4" type="submit">Create seller store</button>
            <a class="btn btn-outline-secondary rounded-pill" href="<?= merchant_url('dashboard') ?>">Back</a>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
