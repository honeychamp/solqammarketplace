<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/solqam-card.css') ?>?v=20261113f">
<?php
$demoPan = '0101C00000002';
$demoDisplay = \App\Services\Card\SolqamCardNumber::display($demoPan);
$demo = [
    'tier'         => 'gold',
    'holder_name'  => 'YOUR NAME',
    'pan'          => $demoPan,
    'pan_grouped'  => $demoDisplay,
    'expires_fmt'  => '11/31',
    'cvv'          => '•••',
    'qr'           => site_url('card/c/' . $demoPan),
    'id_parts'     => \App\Services\Card\SolqamCardNumber::decode($demoPan),
    'product'      => 'Marketplace Card',
    'is_frozen'    => false,
];
?>
<div class="container py-4 py-lg-5">
    <div class="sq-card-hero mb-4">
        <div class="row align-items-center g-4">
            <div class="col-lg-5">
                <div class="small text-uppercase fw-bold" style="letter-spacing:.18em;color:#93c5fd;">Solqam Marketplace</div>
                <h1 class="fw-bold display-5 mb-3">The Solqam Card</h1>
                <p class="lead opacity-90">Your number is built from division, city of that division, member type and a unique serial — like <strong>01-01-C-00000002</strong>. Merchants add a 3-digit shop type, so every garments shop in Pakistan shares <strong>002</strong>. QR sits on the card. Merchants scan it and 5% credits go to your wallet.</p>
                <a class="btn btn-warning rounded-pill px-4 fw-bold" href="<?= site_url('register') ?>">Open my card</a>
                <a class="btn btn-outline-light rounded-pill px-4 ms-2" href="<?= site_url('login?intent=merchant') ?>">Join as merchant</a>
            </div>
            <div class="col-lg-7">
                <?= view('partials/solqam_credit_card', ['card' => $demo, 'interactive' => true, 'hideCvv' => true]) ?>
                <p class="small text-center text-white-50 mt-3 mb-0">QR on top of the card · tap to flip</p>
            </div>
        </div>
    </div>
    <div class="row g-3 mb-4">
        <?php
        $steps = [
            ['n' => '1', 't' => 'Get your number', 'd' => 'Register once. Solqam issues a unique card ID for your city. Golden serials stay reserved.'],
            ['n' => '2', 't' => 'Shop in person', 'd' => 'Show the QR at a partner merchant. They enter the bill. 5% is calculated automatically.'],
            ['n' => '3', 't' => 'Credit stays pending', 'd' => 'The shop pays Solqam that 5% and uploads a screenshot. Nothing hits your wallet until then.'],
            ['n' => '4', 't' => 'Spend on Marketplace', 'd' => 'Admin releases the credit. Use it on Solqam Marketplace checkout — not a bank card.'],
        ];
        foreach ($steps as $st): ?>
            <div class="col-md-6 col-lg-3">
                <div class="sq-how">
                    <div class="sq-how-n"><?= esc($st['n']) ?></div>
                    <div class="fw-bold mb-1"><?= esc($st['t']) ?></div>
                    <div class="small text-muted mb-0"><?= esc($st['d']) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <div class="row g-3">
            <div class="col-md-6">
                <h5 class="fw-bold">Customers</h5>
                <ul class="small text-secondary mb-0">
                    <li>Freeze instantly if the phone is lost.</li>
                    <li>Set a 4-digit shop PIN so only you can authorize a scan.</li>
                    <li>CVV stays on your card back — merchants never see it on POS.</li>
                    <li>Daily credit cap protects the network.</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h5 class="fw-bold">Merchants</h5>
                <ul class="small text-secondary mb-0">
                    <li>Scan QR or type the dashed number at the hub.</li>
                    <li>Cannot scan your own merchant card.</li>
                    <li>Pay the 5% pool, upload one screenshot, Solqam releases wallets.</li>
                    <li>Your ID ends with a national 3-digit shop category (grocery 001, garments 002).</li>
                </ul>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
const flip = document.getElementById('sqCardFlip');
if (flip) flip.addEventListener('click', () => flip.classList.toggle('is-flipped'));
</script>
<?= $this->endSection() ?>
