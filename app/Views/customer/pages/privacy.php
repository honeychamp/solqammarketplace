<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container py-4">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
                <h1 class="h3 fw-bold mb-2">Privacy Policy</h1>
                <p class="text-muted">How Solqam Marketplace collects, uses, and protects personal data. Last updated 21 October 2026.</p>
                <h5 class="fw-bold">Who we are</h5>
                <p class="text-secondary">Solqam Marketplace (solqam.com) is an online marketplace in Pakistan. This Privacy Policy applies to shoppers, sellers, and visitors who use our website or sign in with Google or Facebook.</p>
                <h5 class="fw-bold">Information we collect</h5>
                <ul class="text-secondary">
                    <li>Account details: name, email, phone, password hash, and delivery addresses.</li>
                    <li>If you continue with Google or Facebook: your public name, email, and profile photo, plus the provider’s user id.</li>
                    <li>Orders, payments status, wallet ledger entries, and customer-care messages.</li>
                    <li>Device and log data such as IP address and pages viewed, used to keep the store secure.</li>
                </ul>
                <h5 class="fw-bold">How we use information</h5>
                <ul class="text-secondary">
                    <li>To create your account, process orders, and provide seller tools.</li>
                    <li>To prevent fraud, enforce our policies, and contact you about your orders.</li>
                    <li>We do not sell your personal information.</li>
                </ul>
                <h5 class="fw-bold">Sharing</h5>
                <p class="text-secondary">Sellers see the shipping details needed to fulfill your order. Payment partners receive what they need to process a payment. We may share data if required by Pakistani law.</p>
                <h5 class="fw-bold">Facebook and Google login</h5>
                <p class="text-secondary">When you use Continue with Facebook or Google, those companies share the profile fields you approve. You can disconnect that login by using email sign-in instead and asking us to unlink the social id.</p>
                <h5 class="fw-bold">Retention and security</h5>
                <p class="text-secondary">We keep account and order records as long as the account is active and as required for accounting. Access is limited to people who operate Solqam.</p>
                <h5 class="fw-bold">Your choices</h5>
                <p class="text-secondary mb-0">You may request a copy or deletion of your account data. See our <a href="<?= site_url('data-deletion') ?>">User data deletion</a> page, or contact us from the <a href="<?= site_url('help') ?>">Help Center</a>.</p>
            </div>
        </div>
        <div class="col-lg-4"><?= $this->include('customer/pages/_nav') ?></div>
    </div>
</div>
<?= $this->endSection() ?>
