<?php

namespace App\Commands;

use App\Models\OtpLogModel;
use App\Models\SolqamCardModel;
use App\Models\UserModel;
use App\Services\Auth\AuthService;
use App\Services\Card\CardNetworkService;
use App\Services\Card\SolqamCardNumber;
use App\Services\Platform\SchemaHeal;
use App\Services\Wallet\WalletService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;
use Throwable;

/**
 * Merchant network A–Z: register shop, OTP, approve, customer card, sale, proof, release.
 */
class MerchantE2e extends BaseCommand
{
    protected $group       = 'Solqam';
    protected $name        = 'merchant:e2e';
    protected $description = 'Register a merchant with full shop data and run card sale → pay Solqam → admin release.';

    public function run(array $params)
    {
        $ok     = [];
        $issues = [];

        try {
            SchemaHeal::ensureCardNetwork();
            $ok[] = 'SchemaHeal merchant tables';
        } catch (Throwable $e) {
            CLI::error('SchemaHeal: ' . $e->getMessage());

            return EXIT_ERROR;
        }

        $stamp = substr((string) time(), -6);
        $auth  = new AuthService();
        $net   = new CardNetworkService();
        $users = new UserModel();
        $db    = Database::connect();

        $mPhone = '0398' . $stamp;
        $cPhone = '0397' . $stamp;
        $mEmail = 'merchant.' . $stamp . '@solqam.test';
        $cEmail = 'cardcust.' . $stamp . '@solqam.test';
        $pass   = 'Test#123';

        try {
            $m = $auth->registerDukandar([
                'name'           => 'Hassan Merchant',
                'email'          => $mEmail,
                'phone'          => $mPhone,
                'password'       => $pass,
                'city'           => 'Abbottabad',
                'province'       => 'KPK',
                'division_code'  => '16',
                'city_code'      => '01',
            ], [
                'owner_name'    => 'Hassan Merchant',
                'shop_name'     => 'Hassan Super Mart ' . $stamp,
                'phone'         => $mPhone,
                'email'         => $mEmail,
                'city'          => 'Abbottabad',
                'province'      => 'KPK',
                'whatsapp'      => $mPhone,
                'opening_hours' => '09:00 AM – 11:00 PM',
                'area'          => 'Jinnah Road',
                'address'       => 'Shop 12, Jinnah Road near clock tower',
                'category'       => 'Grocery Store',
                'category_code'  => '001',
                'occupancy_type' => 'owner',
                'business_year'  => 2018,
            ]);
            $ok[] = 'Merchant registered user #' . $m['user_id'];
        } catch (Throwable $e) {
            $issues[] = 'Merchant register: ' . $e->getMessage();
            $this->report($ok, $issues);

            return EXIT_ERROR;
        }

        $shop = $net->profileForUser((int) $m['user_id']);
        if (! $shop) {
            $issues[] = 'Merchant profile missing after register';
            $this->report($ok, $issues);

            return EXIT_ERROR;
        }
        if (($shop['approval_status'] ?? '') !== 'pending') {
            $issues[] = 'New shop should be pending, got ' . ($shop['approval_status'] ?? '');
        } else {
            $ok[] = 'Shop pending approval · ' . ($shop['merchant_code'] ?? 'no code') . ' · ' . ($shop['shop_name'] ?? '');
        }
        if (trim((string) ($shop['province'] ?? '')) === '') {
            $issues[] = 'Province not stored on shop';
        }
        if (trim((string) ($shop['opening_hours'] ?? '')) === '') {
            $issues[] = 'Opening hours not stored';
        }

        $otpRow = (new OtpLogModel())
            ->where('phone', $mPhone)
            ->where('purpose', 'signup')
            ->orderBy('id', 'DESC')
            ->first();
        if (! $otpRow || ! $auth->verifySignupOtp($mPhone, (string) $otpRow['otp_code'])) {
            $issues[] = 'Merchant OTP verify failed';
        } else {
            $ok[] = 'Merchant email OTP verified';
        }

        try {
            $c = $auth->registerCustomer([
                'name'           => 'Ayesha Customer',
                'email'          => $cEmail,
                'phone'          => $cPhone,
                'password'       => $pass,
                'city'           => 'Abbottabad',
                'province'       => 'KPK',
                'division_code'  => '16',
                'city_code'      => '01',
            ]);
            $otpC = (new OtpLogModel())
                ->where('phone', $cPhone)
                ->where('purpose', 'signup')
                ->orderBy('id', 'DESC')
                ->first();
            if ($otpC) {
                $auth->verifySignupOtp($cPhone, (string) $otpC['otp_code']);
            }
            $ok[] = 'Customer registered user #' . $c['user_id'];
        } catch (Throwable $e) {
            $issues[] = 'Customer register: ' . $e->getMessage();
            $this->report($ok, $issues);

            return EXIT_ERROR;
        }

        $card = (new SolqamCardModel())->where('user_id', (int) $c['user_id'])->first();
        if (! $card) {
            try {
                $card = $net->issueCard((int) $c['user_id']);
            } catch (Throwable $e) {
                $issues[] = 'Issue card: ' . $e->getMessage();
            }
        }
        $pan = (string) ($card['pan'] ?? $card['card_code'] ?? '');
        if ($pan === '') {
            $issues[] = 'Customer has no Solqam Card number';
            $this->report($ok, $issues);

            return EXIT_ERROR;
        }
        $ok[] = 'Customer card ' . $pan;
        $custCompact = \App\Services\Card\SolqamCardNumber::normalize($pan);
        if (! preg_match('/^1601C\d{8}$/', $custCompact)) {
            $issues[] = 'Customer PAN should be 1601C + 8 digits, got ' . $custCompact;
        } else {
            $ok[] = 'Customer PAN format 16-01-C (Hazara / Abbottabad)';
        }
        $custParts = \App\Services\Card\SolqamCardNumber::decode($pan);
        if (\App\Services\Card\SolqamGeoCatalog::isGoldenSerial('C', (string) ($custParts['unique'] ?? ''))) {
            $issues[] = 'Customer received a reserved golden serial ' . ($custParts['unique'] ?? '');
        } else {
            $ok[] = 'Golden serial skipped on auto issue';
        }
        $dashed = \App\Services\Card\SolqamCardNumber::display($custCompact);
        try {
            $foundDashed = $net->lookupCard($dashed);
            if ((int) ($foundDashed['customer']['id'] ?? 0) !== (int) $c['user_id']) {
                $issues[] = 'Dashed PAN lookup pointed at the wrong member';
            } else {
                $ok[] = 'Lookup accepts dashed number ' . $dashed;
            }
        } catch (Throwable $e) {
            $issues[] = 'Dashed PAN lookup failed: ' . $e->getMessage();
        }
        $fakeUrl = 'http://localhost:8080/card/c/' . $custCompact;
        try {
            $fromUrl = $net->lookupCard($fakeUrl);
            if ((int) ($fromUrl['customer']['id'] ?? 0) !== (int) $c['user_id']) {
                $issues[] = 'QR URL lookup pointed at the wrong member';
            } else {
                $ok[] = 'Lookup accepts card/c/ QR URL';
            }
            $redacted = $net->redactForPos($fromUrl['card'] ?? []);
            if (($redacted['cvv'] ?? '') !== '•••') {
                $issues[] = 'POS redact did not hide CVV';
            } else {
                $ok[] = 'POS preview hides CVV';
            }
        } catch (Throwable $e) {
            $issues[] = 'QR URL lookup failed: ' . $e->getMessage();
        }

        $net->freeze((int) $c['user_id'], true);
        $froze = false;
        try {
            $net->lookupCard($custCompact);
        } catch (Throwable $e) {
            $froze = true;
            $ok[] = 'Frozen card blocked at POS';
        }
        if (! $froze) {
            $issues[] = 'Frozen card was still lookup-able';
        }
        $net->freeze((int) $c['user_id'], false);
        try {
            $net->lookupCard($custCompact);
            $ok[] = 'Unfreeze restored POS lookup';
        } catch (Throwable $e) {
            $issues[] = 'Unfreeze lookup failed: ' . $e->getMessage();
        }
        try {
            $net->setPin((int) $c['user_id'], '4242');
            $ok[] = 'Shop PIN set';
        } catch (Throwable $e) {
            $issues[] = 'setPin: ' . $e->getMessage();
        }

        $mCard = (new SolqamCardModel())->where('user_id', (int) $m['user_id'])->first();
        $mPan  = (string) ($mCard['pan'] ?? '');
        $mCompact = \App\Services\Card\SolqamCardNumber::normalize($mPan);
        if (! preg_match('/^1601M\d{7}001$/', $mCompact)) {
            $issues[] = 'Merchant PAN should be 1601M + 7 digits + 001 grocery, got ' . $mCompact;
        } else {
            $ok[] = 'Merchant PAN format 16-01-M-•••••••-001 · ' . \App\Services\Card\SolqamCardNumber::display($mCompact);
        }
        if (($shop['category_code'] ?? '') !== '001') {
            $issues[] = 'Merchant category_code should be 001, got ' . ($shop['category_code'] ?? '');
        } else {
            $ok[] = 'National grocery category code 001 stored';
        }

        try {
            $net->recordSale((int) $m['user_id'], $pan, 1200);
            $issues[] = 'Sale before approval should fail';
        } catch (Throwable $blocked) {
            $ok[] = 'Pending shop cannot scan: ' . $blocked->getMessage();
        }

        $net->setApproval((int) $shop['id'], 'approved');
        $ok[] = 'Admin approved merchant';

        $wallet = new WalletService();
        $before = $wallet->getBalance((int) $c['user_id']);
        $pendingBefore = $net->pendingCreditsForCustomer((int) $c['user_id']);

        try {
            $sale = $net->recordSale((int) $m['user_id'], $pan, 2000, '', '4242');
            $ok[] = 'Sale ' . merchant_receipt($sale['id'] ?? 0) . ' bill 2000 credit ' . ($sale['credit_amount'] ?? '');
            if (SolqamCardNumber::normalize((string) ($sale['pan'] ?? '')) !== $custCompact) {
                $issues[] = 'Sale did not store compact PAN, got ' . ($sale['pan'] ?? '');
            } else {
                $ok[] = 'Sale stored compact PAN';
            }
        } catch (Throwable $e) {
            $issues[] = 'recordSale: ' . $e->getMessage();
            $this->report($ok, $issues);

            return EXIT_ERROR;
        }

        $afterSaleBal = $wallet->getBalance((int) $c['user_id']);
        if (abs($afterSaleBal - $before) > 0.01) {
            $issues[] = 'Wallet changed on pending sale (should stay same). before=' . $before . ' after=' . $afterSaleBal;
        } else {
            $ok[] = 'Pending sale did not credit wallet yet';
        }
        $pendingMid = $net->pendingCreditsForCustomer((int) $c['user_id']);
        if ($pendingMid <= $pendingBefore) {
            $issues[] = 'Pending credit did not increase after sale';
        } else {
            $ok[] = 'Pending credit now Rs. ' . number_format($pendingMid, 2);
        }

        $proofDir = FCPATH . 'uploads/dukandar/proofs';
        if (! is_dir($proofDir)) {
            mkdir($proofDir, 0755, true);
        }
        $proofName = 'e2e_' . $stamp . '.png';
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+ip1sAAAAASUVORK5CYII=');
        file_put_contents($proofDir . DIRECTORY_SEPARATOR . $proofName, $png ?: 'proof');
        $proofPath = 'uploads/dukandar/proofs/' . $proofName;

        $noShot = false;
        try {
            $net->applyMerchantPayout((int) $m['user_id'], 40, '', 'no shot');
        } catch (Throwable $e) {
            $noShot = true;
            $ok[] = 'Screenshot required before queue: ' . $e->getMessage();
        }
        if (! $noShot) {
            $issues[] = 'Payout without screenshot was allowed';
        }
        try {
            $part = $net->applyMerchantPayout((int) $m['user_id'], 40, $proofPath, 'partial E2E' . $stamp);
            $ok[] = 'Partial pay Rs. 40 queued for admin ' . ($part['allocated'] ?? '');
        } catch (Throwable $e) {
            $issues[] = 'partial payout: ' . $e->getMessage();
            $this->report($ok, $issues);

            return EXIT_ERROR;
        }
        $midBal = $wallet->getBalance((int) $c['user_id']);
        if (abs($midBal - $before) > 0.05) {
            $issues[] = 'Wallet changed before admin approval. before=' . $before . ' after=' . $midBal;
        } else {
            $ok[] = 'Wallet unchanged until admin approval';
        }
        $admin = $users->where('role', 'admin')->orderBy('id', 'ASC')->first();
        $adminId = (int) ($admin['id'] ?? 1);
        try {
            $net->approvePayout((int) $part['payout_id'], $adminId, 'E2E approve partial');
            $ok[] = 'Admin approved partial payout';
        } catch (Throwable $e) {
            $issues[] = 'approvePayout: ' . $e->getMessage();
        }
        $afterPart = $wallet->getBalance((int) $c['user_id']);
        if (abs($afterPart - ($before + 40)) > 0.05) {
            $issues[] = 'After admin approve wallet expected ' . ($before + 40) . ' got ' . $afterPart;
        } else {
            $ok[] = 'FIFO Rs. 40 released only after admin approval';
        }
        try {
            $rest = $net->applyMerchantPayout((int) $m['user_id'], 60, $proofPath, 'rest E2E' . $stamp);
            $ok[] = 'Rest pay Rs. 60 queued';
            $net->approvePayout((int) $rest['payout_id'], $adminId, 'E2E approve rest');
            $ok[] = 'Admin approved remaining payout';
        } catch (Throwable $e) {
            $issues[] = 'rest payout/approve: ' . $e->getMessage();
        }

        $after = $wallet->getBalance((int) $c['user_id']);
        $expect = round($before + 100.0, 2);
        if (abs($after - $expect) > 0.05) {
            $issues[] = 'Wallet after admin FIFO expected ~' . $expect . ' got ' . $after;
        } else {
            $ok[] = 'Wallet credited Rs. 100 after admin approvals. Balance ' . number_format($after, 2);
        }

        $selfFail = false;
        $ownCard  = (new SolqamCardModel())->where('user_id', (int) $m['user_id'])->first();
        $ownPan   = (string) ($ownCard['pan'] ?? $ownCard['card_code'] ?? '');
        if ($ownPan === '') {
            try {
                $ownCard = $net->issueCard((int) $m['user_id']);
                $ownPan  = (string) ($ownCard['pan'] ?? '');
            } catch (Throwable $e) {
            }
        }
        try {
            $net->recordSale((int) $m['user_id'], $ownPan, 500);
        } catch (Throwable $e) {
            $selfFail = true;
            $ok[] = 'Own-card scan blocked';
        }
        if (! $selfFail) {
            $issues[] = 'Merchant was able to scan own card';
        }

        $lowFail = false;
        try {
            $net->recordSale((int) $m['user_id'], $pan, 10);
        } catch (Throwable $e) {
            $lowFail = true;
            $ok[] = 'Min bill enforced';
        }
        if (! $lowFail) {
            $issues[] = 'Min bill of 50 not enforced';
        }

        $overview = $net->dukandarOverview((int) $m['user_id']);
        if ((float) ($overview['summary']['released'] ?? 0) < 99) {
            $issues[] = 'Overview released total too low';
        } else {
            $ok[] = 'Hub overview released Rs. ' . number_format((float) $overview['summary']['released'], 2);
        }
        $ledger = $overview['customers'] ?? [];
        $foundCust = false;
        foreach ($ledger as $line) {
            if ((int) ($line['customer_user_id'] ?? 0) === (int) $c['user_id'] && (float) ($line['credits'] ?? 0) >= 99) {
                $foundCust = true;
                break;
            }
        }
        if (! $foundCust) {
            $issues[] = 'Merchant customer ledger missing the e2e customer credit';
        } else {
            $ok[] = 'Merchant ledger lists customer credits';
        }

        $cols = $db->getFieldNames('dukandar_profiles');
        foreach (['province', 'whatsapp', 'opening_hours', 'merchant_code', 'category_code'] as $col) {
            if (! in_array($col, $cols, true)) {
                $issues[] = 'Missing column dukandar_profiles.' . $col;
            }
        }
        $scanCols = $db->getFieldNames('dukandar_scans');
        if (! in_array('pan', $scanCols, true)) {
            $issues[] = 'Missing column dukandar_scans.pan';
        } else {
            $ok[] = 'dukandar_scans.pan column ready';
        }

        $this->report($ok, $issues);

        return $issues === [] ? EXIT_SUCCESS : EXIT_ERROR;
    }

    protected function report(array $ok, array $issues): void
    {
        CLI::write('Merchant A–Z', 'green');
        foreach ($ok as $line) {
            CLI::write('  OK  ' . $line, 'green');
        }
        foreach ($issues as $line) {
            CLI::write('  FAIL  ' . $line, 'red');
        }
        CLI::write($issues === [] ? 'PASS' : 'FAIL (' . count($issues) . ')', $issues === [] ? 'green' : 'red');
    }
}
