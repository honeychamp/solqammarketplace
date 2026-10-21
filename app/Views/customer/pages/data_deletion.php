<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container py-4">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
                <h1 class="h3 fw-bold mb-2">User data deletion</h1>
                <p class="text-muted">How to delete your Solqam Marketplace account and the data we hold. This page also covers Facebook Login data-deletion requests.</p>
                <h5 class="fw-bold">Request deletion</h5>
                <ol class="text-secondary">
                    <li>Sign in to solqam.com if you still can.</li>
                    <li>Open the <a href="<?= site_url('help') ?>">Help Center</a> and send a message that says you want your account deleted. Include the email or phone on the account.</li>
                    <li>If you signed in with Facebook or Google, mention that so we can unlink the social login id.</li>
                </ol>
                <h5 class="fw-bold">What we delete</h5>
                <ul class="text-secondary">
                    <li>Name, email, phone, addresses, social login ids, and profile photo URL.</li>
                    <li>Session and login tokens.</li>
                </ul>
                <h5 class="fw-bold">What we may keep</h5>
                <p class="text-secondary">Completed order records needed for tax, dispute, or Pakistani legal requirements may be kept in anonymized or restricted form.</p>
                <h5 class="fw-bold">Timing</h5>
                <p class="text-secondary mb-0">We process deletion requests within 30 days. After deletion you cannot sign in with that account. Facebook users can also remove Solqam from Facebook Settings → Apps and websites; that stops new Facebook login, and you can still ask us to delete the Solqam profile as above.</p>
            </div>
        </div>
        <div class="col-lg-4"><?= $this->include('customer/pages/_nav') ?></div>
    </div>
</div>
<?= $this->endSection() ?>
