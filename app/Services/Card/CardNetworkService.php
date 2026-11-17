<?php

namespace App\Services\Card;

use App\Models\DukandarProfileModel;
use App\Models\DukandarScanModel;
use App\Models\SolqamCardModel;
use App\Models\UserModel;
use App\Services\Platform\SchemaHeal;
use App\Services\Sms\SmsNotifier;
use App\Services\Wallet\WalletService;
use Config\Database;
use RuntimeException;

class CardNetworkService
{
    public const CASHBACK_PERCENT = 5.0;
    public const MIN_BILL         = 50.0;
    public const MAX_BILL         = 500000.0;
    public const MAX_SCANS_DAY    = 0;
    public const MAX_CREDIT_DAY   = 10000.0;
    public const CVV_REQUIRED_AT  = 5000.0;
    public const PAN_PREFIX       = '8808';

    public const CATEGORIES = [
        'Grocery',
        'Pharmacy',
        'Fashion',
        'Electronics',
        'Restaurant',
        'Mobile',
        'Hardware',
        'Beauty',
        'Fuel',
        'General',
    ];

    public const SERVICES = [
        ['slug' => 'health', 'name' => 'Solqam Health', 'icon' => 'bi-heart-pulse', 'blurb' => 'Clinics, labs and pharmacy partners. Pay with Solqam credits when live.', 'url' => ''],
        ['slug' => 'ride', 'name' => 'Solqam Ride', 'icon' => 'bi-car-front', 'blurb' => 'City rides billed to your Solqam Card wallet.', 'url' => ''],
        ['slug' => 'education', 'name' => 'Solqam Education', 'icon' => 'bi-mortarboard', 'blurb' => 'Courses and tuition partners that accept Solqam credits.', 'url' => ''],
        ['slug' => 'bills', 'name' => 'Solqam Bills', 'icon' => 'bi-receipt', 'blurb' => 'Utility and mobile load — credits apply at checkout.', 'url' => ''],
        ['slug' => 'grocery', 'name' => 'Solqam Grocery', 'icon' => 'bi-basket', 'blurb' => 'Neighbourhood stores on the Solqam Card network.', 'url' => '/card/shops'],
    ];

    protected SolqamCardModel $cards;
    protected DukandarProfileModel $shops;
    protected DukandarScanModel $scans;
    protected WalletService $wallet;

    public function __construct()
    {
        SchemaHeal::ensureCardNetwork();
        $this->cards  = new SolqamCardModel();
        $this->shops  = new DukandarProfileModel();
        $this->scans  = new DukandarScanModel();
        $this->wallet = new WalletService();
    }

    public function issueCard(int $userId): array
    {
        if ($userId < 1) {
            throw new RuntimeException('Sign in to open your Solqam Card.');
        }
        $existing = $this->cards->forUser($userId);
        if ($existing) {
            return $this->hydrateCard($existing, $userId);
        }
        for ($i = 0; $i < 8; $i++) {
            $code = 'SQC-' . strtoupper(substr(bin2hex(random_bytes(5)), 0, 8));
            if (! $this->cards->byCode($code)) {
                $this->cards->insert([
                    'user_id'   => $userId,
                    'card_code' => $code,
                    'status'    => 'active',
                    'issued_at' => date('Y-m-d H:i:s'),
                ]);

                return $this->hydrateCard($this->cards->forUser($userId) ?? [], $userId);
            }
        }

        throw new RuntimeException('Could not issue a Solqam Card. Try again.');
    }

    public function customerDashboard(int $userId): array
    {
        $card    = $this->issueCard($userId);
        $balance = $this->wallet->getBalance($userId);
        $pending = $this->pendingCreditsForCustomer($userId);
        $ledger  = $this->wallet->getTransactions($userId, 40);
        $holds   = $this->scans
            ->select('dukandar_scans.*, dukandar_profiles.shop_name, dukandar_profiles.city as shop_city')
            ->join('dukandar_profiles', 'dukandar_profiles.id = dukandar_scans.dukandar_id', 'left')
            ->where('dukandar_scans.customer_user_id', $userId)
            ->whereIn('dukandar_scans.status', ['pending_payment', 'processing'])
            ->orderBy('dukandar_scans.created_at', 'ASC')
            ->orderBy('dukandar_scans.id', 'ASC')
            ->findAll(40);
        $insights = $this->customerInsights($userId);
        $card['tier'] = $insights['tier'];
        if (($card['id'] ?? 0) && ($card['tier'] ?? '') !== ($insights['tier'] ?? '')) {
            try {
                $this->cards->update((int) $card['id'], ['tier' => $insights['tier']]);
            } catch (\Throwable $e) {
            }
        }
        $visitsHistory = $this->scans
            ->select('dukandar_scans.*, dukandar_profiles.shop_name, dukandar_profiles.city as shop_city, dukandar_profiles.category')
            ->join('dukandar_profiles', 'dukandar_profiles.id = dukandar_scans.dukandar_id', 'left')
            ->where('dukandar_scans.customer_user_id', $userId)
            ->orderBy('dukandar_scans.id', 'DESC')
            ->findAll(12);

        return [
            'card'           => $card,
            'balance'        => $balance,
            'pending'        => $pending,
            'available'      => $balance,
            'ledger'         => $ledger,
            'holds'          => $holds,
            'visits_history' => $visitsHistory,
            'share_url'      => (string) ($card['share_url'] ?? ''),
            'services'       => self::SERVICES,
            'categories'     => array_column(SolqamGeoCatalog::merchantCategories(), 'name') ?: self::CATEGORIES,
            'insights'       => $insights,
            'qrPayload'      => (string) ($card['share_url'] ?? $card['qr'] ?? ''),
            'cvvRequiredAt'  => self::CVV_REQUIRED_AT,
        ];
    }

    public function freeze(int $userId, bool $frozen): void
    {
        $card = $this->issueCard($userId);
        $this->cards->update((int) $card['id'], [
            'frozen_at' => $frozen ? date('Y-m-d H:i:s') : null,
            'status'    => $frozen ? 'frozen' : 'active',
        ]);
    }

    public function setPin(int $userId, string $pin): void
    {
        $pin = preg_replace('/\D+/', '', $pin) ?? '';
        if (! preg_match('/^\d{4}$/', $pin)) {
            throw new RuntimeException('PIN must be exactly 4 digits.');
        }
        $card = $this->issueCard($userId);
        $this->cards->update((int) $card['id'], [
            'pin_hash' => password_hash($pin, PASSWORD_DEFAULT),
        ]);
    }

    public function rotateCvv(int $userId): string
    {
        $card = $this->issueCard($userId);
        $cvv  = str_pad((string) random_int(0, 999), 3, '0', STR_PAD_LEFT);
        $this->cards->update((int) $card['id'], ['cvv' => $cvv]);

        return $cvv;
    }

    public function customerInsights(int $userId): array
    {
        $db     = Database::connect();
        $month  = $db->table('dukandar_scans')
            ->selectSum('credit_amount', 'total')
            ->where('customer_user_id', $userId)
            ->where('status', 'released')
            ->where('created_at >=', date('Y-m-01 00:00:00'))
            ->get()->getRowArray();
        $life   = $db->table('dukandar_scans')
            ->selectSum('credit_amount', 'total')
            ->where('customer_user_id', $userId)
            ->where('status', 'released')
            ->get()->getRowArray();
        $today  = $db->table('dukandar_scans')
            ->selectSum('credit_amount', 'total')
            ->where('customer_user_id', $userId)
            ->where('created_at >=', date('Y-m-d 00:00:00'))
            ->whereNotIn('status', ['rejected'])
            ->get()->getRowArray();
        $visits = (int) $db->table('dukandar_scans')->where('customer_user_id', $userId)->countAllResults();
        $earned = round((float) ($life['total'] ?? 0), 2);
        $tier   = 'blue';
        if ($earned >= 15000) {
            $tier = 'platinum';
        } elseif ($earned >= 2000) {
            $tier = 'gold';
        }
        $todayUsed = round((float) ($today['total'] ?? 0), 2);

        return [
            'month_earned'    => round((float) ($month['total'] ?? 0), 2),
            'lifetime_earned' => $earned,
            'visits'          => $visits,
            'today_used'      => $todayUsed,
            'today_left'      => max(0, round(self::MAX_CREDIT_DAY - $todayUsed, 2)),
            'tier'            => $tier,
            'tier_label'      => $tier === 'platinum' ? 'Platinum' : ($tier === 'gold' ? 'Gold' : 'Blue'),
        ];
    }

    public function pendingCreditsForCustomer(int $userId): float
    {
        $row = Database::connect()->query(
            'SELECT COALESCE(SUM(GREATEST(credit_amount - COALESCE(paid_amount, 0) - COALESCE(queued_amount, 0), 0)), 0) AS total
             FROM dukandar_scans
             WHERE customer_user_id = ?
               AND status IN ("pending_payment", "processing")',
            [$userId]
        )->getRowArray();

        return round((float) ($row['total'] ?? 0), 2);
    }

    public function shops(array $filters = []): array
    {
        return $this->shops->publicDirectory($filters);
    }

    public function cities(): array
    {
        $rows = Database::connect()->table('dukandar_profiles')
            ->select('city')
            ->where('approval_status', 'approved')
            ->where('city !=', '')
            ->groupBy('city')
            ->orderBy('city', 'ASC')
            ->get()
            ->getResultArray();

        return array_values(array_filter(array_map(static fn ($r) => (string) ($r['city'] ?? ''), $rows)));
    }

    public function profileForUser(int $userId): ?array
    {
        return $this->shops->getByUserId($userId);
    }

    public function upsertProfile(int $userId, array $data, bool $resetApproval = false): int
    {
        $row = [
            'user_id'    => $userId,
            'owner_name' => trim((string) ($data['owner_name'] ?? '')),
            'shop_name'  => trim((string) ($data['shop_name'] ?? '')),
            'phone'      => trim((string) ($data['phone'] ?? '')),
            'email'      => trim((string) ($data['email'] ?? '')),
            'city'          => trim((string) ($data['city'] ?? '')),
            'province'      => trim((string) ($data['province'] ?? '')),
            'whatsapp'      => trim((string) ($data['whatsapp'] ?? '')),
            'opening_hours' => trim((string) ($data['opening_hours'] ?? '')),
            'area'          => trim((string) ($data['area'] ?? '')),
            'address'    => trim((string) ($data['address'] ?? '')),
            'category'      => SolqamGeoCatalog::resolveMerchantCategory((string) ($data['category_code'] ?? $data['category'] ?? ''))['name'],
            'category_code' => SolqamGeoCatalog::resolveMerchantCategory((string) ($data['category_code'] ?? $data['category'] ?? ''))['code'],
            'shop_image'     => $data['shop_image'] ?? null,
            'shop_image_in'  => $data['shop_image_in'] ?? null,
            'occupancy_type' => self::normalizeOccupancy($data['occupancy_type'] ?? ''),
            'business_year'  => self::normalizeBusinessYear($data['business_year'] ?? null),
            'lat'            => $data['lat'] ?? null,
            'lng'            => $data['lng'] ?? null,
        ];
        $existing = $this->shops->getByUserId($userId);
        if ($existing) {
            if ($resetApproval && ($existing['approval_status'] ?? '') === 'rejected') {
                $row['approval_status']  = 'pending';
                $row['rejection_reason'] = null;
            }
            unset($row['user_id']);
            if (empty($row['shop_image'])) {
                unset($row['shop_image']);
            }
            if (empty($row['shop_image_in'])) {
                unset($row['shop_image_in']);
            }
            if ($row['occupancy_type'] === null) {
                unset($row['occupancy_type']);
            }
            if ($row['business_year'] === null) {
                unset($row['business_year']);
            }
            $this->shops->update((int) $existing['id'], $row);
            $oldCat = (string) ($existing['category_code'] ?? '');
            if ($oldCat !== (string) $row['category_code']) {
                try {
                    $this->refreshMerchantCategoryPan($userId);
                } catch (\Throwable $e) {
                }
            }

            return (int) $existing['id'];
        }
        $row['approval_status'] = 'pending';
        $row['accepts_card']    = 1;
        $this->shops->insert($row);
        $id = (int) $this->shops->getInsertID();
        try {
            $this->remintAsMerchant($userId);
        } catch (\Throwable $e) {
            log_message('error', 'Merchant card remint: ' . $e->getMessage());
        }
        if ($id > 0) {
            $this->shops->update($id, [
                'merchant_code' => 'M' . str_pad((string) $id, 7, '0', STR_PAD_LEFT),
            ]);
        }

        return $id;
    }

    public function posContext(int $merchantUserId): array
    {
        $shop = $this->profileForUser($merchantUserId);
        $id   = (int) ($shop['id'] ?? 0);
        $used = 0;
        if ($id > 0) {
            $used = $this->scans->where('dukandar_id', $id)
                ->where('created_at >=', date('Y-m-d 00:00:00'))
                ->whereNotIn('status', ['rejected'])
                ->countAllResults();
        }

        $last = null;
        if ($id > 0) {
            $last = $this->scans->where('dukandar_id', $id)->orderBy('id', 'DESC')->first();
        }

        return [
            'shop'        => $shop,
            'scans_today' => $used,
            'scans_left'  => null,
            'min_bill'    => self::MIN_BILL,
            'max_bill'    => self::MAX_BILL,
            'percent'     => self::CASHBACK_PERCENT,
            'last_sale'   => $last,
        ];
    }

    public function shopHistoryForCustomer(int $merchantUserId, int $customerUserId): array
    {
        $shop = $this->profileForUser($merchantUserId);
        $id   = (int) ($shop['id'] ?? 0);
        if ($id < 1 || $customerUserId < 1) {
            return [];
        }

        return $this->scans->where('dukandar_id', $id)
            ->where('customer_user_id', $customerUserId)
            ->orderBy('id', 'DESC')
            ->findAll(8);
    }

    public function lookupCard(string $raw): array
    {
        $code = $this->extractCardCode($raw);
        $card = $this->findCard($raw);
        if (! $card) {
            throw new RuntimeException('No Solqam Card found. Scan the QR or type the number (example 01-01-C-00000002).');
        }
        $user = (new UserModel())->find((int) $card['user_id']);
        if (! $user) {
            throw new RuntimeException('Customer account not found for this card.');
        }
        $uid  = (int) $user['id'];
        $card = $this->hydrateCard($card, $uid);
        if (($card['status'] ?? '') === 'frozen' || ! empty($card['frozen_at'])) {
            throw new RuntimeException('This Solqam Card is frozen. The customer must unfreeze it in their wallet.');
        }
        if (($card['status'] ?? '') !== 'active') {
            throw new RuntimeException('This Solqam Card is not active.');
        }
        $exp = (string) ($card['expires_at'] ?? '');
        if ($exp !== '' && $exp !== '0000-00-00' && $exp < date('Y-m-d')) {
            throw new RuntimeException('This Solqam Card has expired. Ask the customer to open the wallet for a new virtual card.');
        }

        return [
            'card'      => $card,
            'customer'  => [
                'id'        => $uid,
                'name'      => (string) ($user['name'] ?? 'Customer'),
                'phone'     => $this->maskPhone((string) ($user['phone'] ?? '')),
                'phone_raw' => (string) ($user['phone'] ?? ''),
            ],
            'balance'   => $this->wallet->getBalance($uid),
            'pending'   => $this->pendingCreditsForCustomer($uid),
            'insights'  => $this->customerInsights($uid),
            'visits'    => $this->scans->where('customer_user_id', $uid)->orderBy('id', 'DESC')->findAll(8),
            'code'      => $code,
        ];
    }

    public function redactForPos(array $card): array
    {
        unset($card['pin_hash']);
        $card['cvv'] = '•••';
        $card['has_pin'] = ! empty($card['has_pin']);

        return $card;
    }

    public function recordSale(int $dukandarUserId, string $rawCode, float $billAmount, string $cvv = '', string $pin = ''): array
    {
        $shop = $this->requireApprovedShop($dukandarUserId);
        $bill = round($billAmount, 2);
        if ($bill < self::MIN_BILL) {
            throw new RuntimeException('Minimum bill is Rs. ' . number_format(self::MIN_BILL, 0) . '.');
        }
        if ($bill > self::MAX_BILL) {
            throw new RuntimeException('Bill is too large. Split the sale.');
        }
        $found  = $this->lookupCard($rawCode);
        if ($bill >= self::CVV_REQUIRED_AT) {
            $this->assertCvv($found['card'], $cvv);
        } elseif ($cvv !== '') {
            $this->assertCvv($found['card'], $cvv);
        }
        if (! empty($found['card']['has_pin'])) {
            $this->assertPin($found['card'], $pin);
        }
        $credit = round($bill * (self::CASHBACK_PERCENT / 100), 2);
        $custId = (int) $found['customer']['id'];
        if ((int) $found['customer']['id'] === $dukandarUserId) {
            throw new RuntimeException('You cannot scan your own Solqam Card.');
        }
        $recent = $this->scans->where('dukandar_id', (int) $shop['id'])
            ->where('customer_user_id', $custId)
            ->where('created_at >=', date('Y-m-d H:i:s', time() - 120))
            ->whereNotIn('status', ['rejected'])
            ->first();
        if ($recent) {
            throw new RuntimeException('This card was just scanned here. Wait two minutes or correct the bill on the previous sale.');
        }
        $daySum = Database::connect()->table('dukandar_scans')
            ->selectSum('credit_amount', 'total')
            ->where('customer_user_id', $custId)
            ->where('created_at >=', date('Y-m-d 00:00:00'))
            ->whereNotIn('status', ['rejected'])
            ->get()
            ->getRowArray();
        if (((float) ($daySum['total'] ?? 0) + $credit) > self::MAX_CREDIT_DAY) {
            throw new RuntimeException('This customer has reached the daily Solqam Card credit cap.');
        }

        $this->scans->insert([
            'dukandar_id'      => (int) $shop['id'],
            'customer_user_id' => $custId,
            'card_code'        => (string) $found['card']['card_code'],
            'pan'              => SolqamCardNumber::normalize((string) ($found['card']['pan'] ?? '')),
            'bill_amount'      => $bill,
            'credit_percent'   => self::CASHBACK_PERCENT,
            'credit_amount'    => $credit,
            'paid_amount'      => 0,
            'queued_amount'    => 0,
            'status'           => 'pending_payment',
        ]);
        $id = (int) $this->scans->getInsertID();
        try {
            SmsNotifier::send(
                (string) $found['customer']['phone_raw'],
                'Solqam Card: Rs. ' . number_format($credit, 0) . ' credit is pending from ' . ($shop['shop_name'] ?? 'a partner shop') . '. It is released after Solqam confirms the shop payment.',
                'card_credit_pending'
            );
        } catch (\Throwable $e) {
        }

        return $this->scans->find($id) ?: [];
    }

    public function dukandarOverview(int $userId): array
    {
        $shop = $this->profileForUser($userId);
        $id   = (int) ($shop['id'] ?? 0);
        $rows = [];
        if ($id) {
            $rows = $this->scans
                ->select('dukandar_scans.*, users.name as customer_name, users.phone as customer_phone')
                ->join('users', 'users.id = dukandar_scans.customer_user_id', 'left')
                ->where('dukandar_scans.dukandar_id', $id)
                ->orderBy('dukandar_scans.id', 'DESC')
                ->findAll(200);
        }
        $sum  = [
            'sales'      => 0.0,
            'credits'    => 0.0,
            'pending'    => 0.0,
            'processing' => 0.0,
            'released'   => 0.0,
            'visits'     => 0,
        ];
        $today = [
            'sales'   => 0.0,
            'credits' => 0.0,
            'scans'   => 0,
        ];
        if ($id) {
            $db  = Database::connect();
            $agg = $db->query(
                'SELECT COUNT(*) AS visits,
                        COALESCE(SUM(bill_amount),0) AS sales,
                        COALESCE(SUM(credit_amount),0) AS credits,
                        COALESCE(SUM(CASE WHEN status <> "rejected" THEN GREATEST(credit_amount - COALESCE(paid_amount, 0) - COALESCE(queued_amount, 0), 0) ELSE 0 END),0) AS pending,
                        COALESCE(SUM(CASE WHEN status <> "rejected" THEN COALESCE(queued_amount, 0) ELSE 0 END),0) AS processing,
                        COALESCE(SUM(LEAST(COALESCE(paid_amount, 0), credit_amount)),0) AS released
                 FROM dukandar_scans WHERE dukandar_id = ? AND status <> "rejected"',
                [$id]
            )->getRowArray() ?: [];
            $sum['visits']     = (int) ($agg['visits'] ?? 0);
            $sum['sales']      = (float) ($agg['sales'] ?? 0);
            $sum['credits']    = (float) ($agg['credits'] ?? 0);
            $sum['pending']    = (float) ($agg['pending'] ?? 0);
            $sum['processing'] = (float) ($agg['processing'] ?? 0);
            $sum['released']   = (float) ($agg['released'] ?? 0);

            $day = $db->query(
                'SELECT COUNT(*) AS scans,
                        COALESCE(SUM(bill_amount),0) AS sales,
                        COALESCE(SUM(credit_amount),0) AS credits
                 FROM dukandar_scans
                 WHERE dukandar_id = ? AND status <> "rejected" AND created_at >= ?',
                [$id, date('Y-m-d 00:00:00')]
            )->getRowArray() ?: [];
            $today['scans']   = (int) ($day['scans'] ?? 0);
            $today['sales']   = (float) ($day['sales'] ?? 0);
            $today['credits'] = (float) ($day['credits'] ?? 0);
        }

        $customers = $id ? $this->shopCustomerLedger($id) : [];
        $fifo      = $id ? $this->fifoUnpaidQueue($id) : [];

        return [
            'shop'      => $shop,
            'scans'     => $rows,
            'summary'   => $sum,
            'today'     => $today,
            'customers' => $customers,
            'fifo'      => $fifo,
        ];
    }

    /**
     * Per-customer credit history for one shop (all-time, unpaid split out).
     *
     * @return list<array<string, mixed>>
     */
    public function shopCustomerLedger(int $dukandarId): array
    {
        if ($dukandarId < 1) {
            return [];
        }
        $rows = Database::connect()->query(
            'SELECT s.customer_user_id,
                    u.name AS customer_name,
                    u.phone AS customer_phone,
                    MAX(s.pan) AS pan,
                    MAX(s.card_code) AS card_code,
                    COUNT(*) AS visits,
                    COALESCE(SUM(s.bill_amount), 0) AS sales,
                    COALESCE(SUM(CASE WHEN s.status <> "rejected" THEN s.credit_amount ELSE 0 END), 0) AS credits,
                    COALESCE(SUM(CASE WHEN s.status <> "rejected" THEN GREATEST(s.credit_amount - COALESCE(s.paid_amount, 0) - COALESCE(s.queued_amount, 0), 0) ELSE 0 END), 0) AS pending,
                    COALESCE(SUM(CASE WHEN s.status <> "rejected" THEN COALESCE(s.queued_amount, 0) ELSE 0 END), 0) AS processing,
                    COALESCE(SUM(LEAST(COALESCE(s.paid_amount, 0), s.credit_amount)), 0) AS released
             FROM dukandar_scans s
             LEFT JOIN users u ON u.id = s.customer_user_id
             WHERE s.dukandar_id = ?
             GROUP BY s.customer_user_id, u.name, u.phone
             ORDER BY pending DESC, credits DESC, visits DESC',
            [$dukandarId]
        )->getResultArray();

        return array_map(static function (array $r) {
            $r['customer_user_id'] = (int) ($r['customer_user_id'] ?? 0);
            $r['visits']           = (int) ($r['visits'] ?? 0);
            $r['sales']            = (float) ($r['sales'] ?? 0);
            $r['credits']          = (float) ($r['credits'] ?? 0);
            $r['pending']          = (float) ($r['pending'] ?? 0);
            $r['processing']       = (float) ($r['processing'] ?? 0);
            $r['released']         = (float) ($r['released'] ?? 0);

            return $r;
        }, $rows);
    }

    /**
     * Admin settlement: one row per shop with customer lines and unpaid / processing totals.
     *
     * @return list<array<string, mixed>>
     */
    public function adminShopSettlements(): array
    {
        $shops = Database::connect()->query(
            'SELECT p.id, p.shop_name, p.city, p.merchant_code, p.owner_name,
                    COALESCE(SUM(CASE WHEN s.status <> "rejected" THEN s.credit_amount ELSE 0 END), 0) AS credits,
                    COALESCE(SUM(CASE WHEN s.status <> "rejected" THEN GREATEST(s.credit_amount - COALESCE(s.paid_amount, 0) - COALESCE(s.queued_amount, 0), 0) ELSE 0 END), 0) AS pending,
                    COALESCE(SUM(CASE WHEN s.status <> "rejected" THEN COALESCE(s.queued_amount, 0) ELSE 0 END), 0) AS processing,
                    COALESCE(SUM(LEAST(COALESCE(s.paid_amount, 0), s.credit_amount)), 0) AS released,
                    COUNT(s.id) AS visits
             FROM dukandar_profiles p
             LEFT JOIN dukandar_scans s ON s.dukandar_id = p.id
             GROUP BY p.id, p.shop_name, p.city, p.merchant_code, p.owner_name
             HAVING visits > 0
             ORDER BY pending DESC, processing DESC, p.shop_name ASC'
        )->getResultArray();

        foreach ($shops as &$shop) {
            $id = (int) $shop['id'];
            $shop['id']         = $id;
            $shop['credits']    = (float) ($shop['credits'] ?? 0);
            $shop['pending']    = (float) ($shop['pending'] ?? 0);
            $shop['processing'] = (float) ($shop['processing'] ?? 0);
            $shop['released']   = (float) ($shop['released'] ?? 0);
            $shop['visits']     = (int) ($shop['visits'] ?? 0);
            $shop['customers']  = $this->shopCustomerLedger($id);
        }
        unset($shop);

        return $shops;
    }

    public function shopCreditTotals(int $dukandarId): array
    {
        $row = Database::connect()->query(
            'SELECT COUNT(*) AS visits,
                    COALESCE(SUM(CASE WHEN status <> "rejected" THEN credit_amount ELSE 0 END), 0) AS credits,
                    COALESCE(SUM(CASE WHEN status <> "rejected" THEN GREATEST(credit_amount - COALESCE(paid_amount, 0) - COALESCE(queued_amount, 0), 0) ELSE 0 END), 0) AS pending,
                    COALESCE(SUM(CASE WHEN status <> "rejected" THEN COALESCE(queued_amount, 0) ELSE 0 END), 0) AS processing,
                    COALESCE(SUM(LEAST(COALESCE(paid_amount, 0), credit_amount)), 0) AS released,
                    COALESCE(SUM(CASE WHEN status <> "rejected" THEN bill_amount ELSE 0 END), 0) AS sales
             FROM dukandar_scans WHERE dukandar_id = ?',
            [$dukandarId]
        )->getRowArray() ?: [];

        return [
            'visits'     => (int) ($row['visits'] ?? 0),
            'credits'    => (float) ($row['credits'] ?? 0),
            'pending'    => (float) ($row['pending'] ?? 0),
            'processing' => (float) ($row['processing'] ?? 0),
            'released'   => (float) ($row['released'] ?? 0),
            'sales'      => (float) ($row['sales'] ?? 0),
        ];
    }

    public function releaseShopProcessing(int $dukandarId, int $adminId, string $note = ''): int
    {
        if ($dukandarId < 1) {
            throw new RuntimeException('Merchant not found.');
        }
        $payouts = Database::connect()->table('dukandar_payouts')
            ->where('dukandar_id', $dukandarId)
            ->where('status', 'pending')
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();
        $n = 0;
        foreach ($payouts as $p) {
            $n += $this->approvePayout((int) $p['id'], $adminId, $note);
        }
        if ($n < 1) {
            throw new RuntimeException('No merchant payment waiting for admin approval.');
        }

        return $n;
    }

    public function remainingDue(array $row): float
    {
        $status = (string) ($row['status'] ?? '');
        if ($status === 'rejected' || $status === 'released') {
            return 0.0;
        }
        $credit = round((float) ($row['credit_amount'] ?? 0), 2);
        $paid   = round((float) ($row['paid_amount'] ?? 0), 2);
        $queued = round((float) ($row['queued_amount'] ?? 0), 2);

        return max(0.0, round($credit - $paid - $queued, 2));
    }

    /**
     * Oldest unpaid credits first (9am customer before later visits).
     *
     * @return list<array<string, mixed>>
     */
    public function fifoUnpaidQueue(int $dukandarId): array
    {
        if ($dukandarId < 1) {
            return [];
        }
        $rows = $this->scans
            ->select('dukandar_scans.*, users.name as customer_name, users.phone as customer_phone')
            ->join('users', 'users.id = dukandar_scans.customer_user_id', 'left')
            ->where('dukandar_scans.dukandar_id', $dukandarId)
            ->whereNotIn('dukandar_scans.status', ['rejected', 'released'])
            ->orderBy('dukandar_scans.created_at', 'ASC')
            ->orderBy('dukandar_scans.id', 'ASC')
            ->findAll(200);
        $out = [];
        foreach ($rows as $row) {
            $due = $this->remainingDue($row);
            if ($due <= 0) {
                continue;
            }
            $row['remaining'] = $due;
            $out[]            = $row;
        }

        return $out;
    }

    /**
     * Preview how a payment amount is split: first-come, first-released.
     *
     * @return list<array{scan_id:int,customer:string,take:float,remaining_after:float,fully_released:bool}>
     */
    public function previewFifoAllocation(int $dukandarId, float $amount): array
    {
        $left = round($amount, 2);
        $plan = [];
        foreach ($this->fifoUnpaidQueue($dukandarId) as $row) {
            if ($left <= 0) {
                break;
            }
            $due  = (float) $row['remaining'];
            $take = min($due, $left);
            $plan[] = [
                'scan_id'         => (int) $row['id'],
                'customer'        => (string) ($row['customer_name'] ?? 'Customer'),
                'when'            => (string) ($row['created_at'] ?? ''),
                'take'            => $take,
                'remaining_after' => round($due - $take, 2),
                'fully_released'  => ($due - $take) <= 0.009,
            ];
            $left = round($left - $take, 2);
        }

        return $plan;
    }

    /**
     * Merchant pays Solqam (full or partial) with screenshot. FIFO queue only —
     * wallets stay pending until admin approves this payout.
     *
     * @return array{payout_id:int,allocated:float,customers:int,lines:list<array<string,mixed>>}
     */
    public function applyMerchantPayout(int $dukandarUserId, float $amount, string $path, string $note = '', ?int $onlyScanId = null): array
    {
        $shop = $this->requireApprovedShop($dukandarUserId);
        $id   = (int) $shop['id'];
        $pay  = round($amount, 2);
        if ($pay <= 0) {
            throw new RuntimeException('Enter the amount you sent to Solqam.');
        }
        if (trim($path) === '') {
            throw new RuntimeException('Payment screenshot is required. Admin cannot approve without it.');
        }
        $queue = $this->fifoUnpaidQueue($id);
        if ($onlyScanId) {
            $queue = array_values(array_filter($queue, static fn ($r) => (int) $r['id'] === $onlyScanId));
        }
        $unpaid = 0.0;
        foreach ($queue as $row) {
            $unpaid += (float) $row['remaining'];
        }
        $unpaid = round($unpaid, 2);
        if ($unpaid <= 0) {
            throw new RuntimeException('No unpaid credits to cover.');
        }
        if ($pay > $unpaid + 0.009) {
            throw new RuntimeException('Amount is more than unpaid Rs. ' . number_format($unpaid, 0) . '.');
        }

        $db = Database::connect();
        $db->table('dukandar_payouts')->insert([
            'dukandar_id' => $id,
            'amount'      => $pay,
            'allocated'   => 0,
            'proof_path'  => $path,
            'proof_note'  => $note !== '' ? $note : null,
            'status'      => 'pending',
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
        $payoutId = (int) $db->insertID();
        $left     = $pay;
        $lines    = [];
        $custIds  = [];

        foreach ($queue as $row) {
            if ($left <= 0) {
                break;
            }
            $scanId = (int) $row['id'];
            $due    = $this->remainingDue($row);
            if ($due <= 0) {
                continue;
            }
            $take   = min($due, $left);
            $queued = round((float) ($row['queued_amount'] ?? 0) + $take, 2);
            $this->scans->update($scanId, [
                'queued_amount' => $queued,
                'proof_path'    => $path,
                'proof_note'    => $note !== '' ? $note : ($row['proof_note'] ?? null),
                'submitted_at'  => date('Y-m-d H:i:s'),
                'status'        => 'processing',
            ]);
            $db->table('dukandar_payout_lines')->insert([
                'payout_id'  => $payoutId,
                'scan_id'    => $scanId,
                'amount'     => $take,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $lines[] = [
                'scan_id'  => $scanId,
                'customer' => (string) ($row['customer_name'] ?? 'Customer'),
                'take'     => $take,
                'when'     => (string) ($row['created_at'] ?? ''),
            ];
            $custIds[(int) $row['customer_user_id']] = true;
            $left = round($left - $take, 2);
        }

        $allocated = round($pay - $left, 2);
        $db->table('dukandar_payouts')->where('id', $payoutId)->update(['allocated' => $allocated]);

        return [
            'payout_id'  => $payoutId,
            'allocated'  => $allocated,
            'customers'  => count($custIds),
            'lines'      => $lines,
        ];
    }

    /**
     * Admin approves a merchant screenshot. FIFO lines are released to wallets in visit order.
     */
    public function approvePayout(int $payoutId, int $adminId, string $note = ''): int
    {
        $db     = Database::connect();
        $payout = $db->table('dukandar_payouts')->where('id', $payoutId)->get()->getRowArray();
        if (! $payout) {
            throw new RuntimeException('Payment not found.');
        }
        if (($payout['status'] ?? '') === 'approved') {
            return 0;
        }
        if (($payout['status'] ?? '') === 'rejected') {
            throw new RuntimeException('This payment was rejected.');
        }
        if (trim((string) ($payout['proof_path'] ?? '')) === '') {
            throw new RuntimeException('No screenshot on this payment. Cannot approve.');
        }
        $lines = $db->table('dukandar_payout_lines')
            ->where('payout_id', $payoutId)
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();
        $n = 0;
        foreach ($lines as $line) {
            $scan = $this->scans->find((int) $line['scan_id']);
            if (! $scan || ($scan['status'] ?? '') === 'rejected') {
                continue;
            }
            $take = round((float) $line['amount'], 2);
            if ($take <= 0) {
                continue;
            }
            $room = max(0.0, round((float) $scan['credit_amount'] - (float) ($scan['paid_amount'] ?? 0), 2));
            $take = min($take, $room);
            if ($take <= 0) {
                continue;
            }
            $ok = $this->wallet->credit(
                (int) $scan['customer_user_id'],
                $take,
                'solqam_card',
                (int) $scan['id'],
                'Solqam Card 5% sale #' . $scan['id'] . ' (admin approved)'
            );
            if (! $ok) {
                throw new RuntimeException('Wallet credit failed for sale #' . $scan['id']);
            }
            $paid   = round((float) ($scan['paid_amount'] ?? 0) + $take, 2);
            $queued = max(0.0, round((float) ($scan['queued_amount'] ?? 0) - $take, 2));
            $full   = $paid + 0.009 >= (float) $scan['credit_amount'];
            $this->scans->update((int) $scan['id'], [
                'paid_amount'   => $paid,
                'queued_amount' => $queued,
                'status'        => $full ? 'released' : ($queued > 0 ? 'processing' : 'pending_payment'),
                'released_at'   => $full ? date('Y-m-d H:i:s') : ($scan['released_at'] ?? null),
                'admin_note'    => $note !== '' ? $note : ('payout_' . $payoutId . '_admin_' . $adminId),
            ]);
            $user = (new UserModel())->find((int) $scan['customer_user_id']);
            try {
                SmsNotifier::send(
                    (string) ($user['phone'] ?? ''),
                    'Solqam Card: Rs. ' . number_format($take, 0) . ' is now in your wallet.',
                    'card_credit_released'
                );
            } catch (\Throwable $e) {
            }
            $n++;
        }
        $db->table('dukandar_payouts')->where('id', $payoutId)->update(['status' => 'approved']);

        return max(1, $n);
    }

    public function rejectPayout(int $payoutId, string $reason): void
    {
        $db     = Database::connect();
        $payout = $db->table('dukandar_payouts')->where('id', $payoutId)->get()->getRowArray();
        if (! $payout || ($payout['status'] ?? '') !== 'pending') {
            throw new RuntimeException('This payment cannot be rejected.');
        }
        $lines = $db->table('dukandar_payout_lines')->where('payout_id', $payoutId)->get()->getResultArray();
        foreach ($lines as $line) {
            $scan = $this->scans->find((int) $line['scan_id']);
            if (! $scan) {
                continue;
            }
            $queued = max(0.0, round((float) ($scan['queued_amount'] ?? 0) - (float) $line['amount'], 2));
            $this->scans->update((int) $scan['id'], [
                'queued_amount' => $queued,
                'status'        => $queued > 0 ? 'processing' : 'pending_payment',
                'admin_note'    => $reason,
            ]);
        }
        $db->table('dukandar_payouts')->where('id', $payoutId)->update(['status' => 'rejected']);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function pendingPayouts(?int $dukandarId = null): array
    {
        $db = Database::connect();
        $b  = $db->table('dukandar_payouts p')
            ->select('p.*, dukandar_profiles.shop_name, dukandar_profiles.city, dukandar_profiles.merchant_code, dukandar_profiles.owner_name')
            ->join('dukandar_profiles', 'dukandar_profiles.id = p.dukandar_id', 'left')
            ->where('p.status', 'pending')
            ->orderBy('p.id', 'ASC');
        if ($dukandarId) {
            $b->where('p.dukandar_id', $dukandarId);
        }
        $rows = $b->get()->getResultArray();
        foreach ($rows as &$row) {
            $row['lines'] = $db->query(
                'SELECT l.amount, s.id AS scan_id, s.created_at, s.pan, s.card_code, s.credit_amount, u.name AS customer_name, u.phone AS customer_phone
                 FROM dukandar_payout_lines l
                 JOIN dukandar_scans s ON s.id = l.scan_id
                 LEFT JOIN users u ON u.id = s.customer_user_id
                 WHERE l.payout_id = ?
                 ORDER BY s.created_at ASC, s.id ASC',
                [(int) $row['id']]
            )->getResultArray();
        }
        unset($row);

        return $rows;
    }

    public function submitProof(int $dukandarUserId, int $scanId, string $path, string $note = ''): void
    {
        $shop = $this->requireApprovedShop($dukandarUserId);
        $row  = $this->scans->find($scanId);
        if (! $row || (int) $row['dukandar_id'] !== (int) $shop['id']) {
            throw new RuntimeException('Sale not found.');
        }
        $due = $this->remainingDue($row);
        if ($due <= 0) {
            throw new RuntimeException('This sale is already paid.');
        }
        $this->applyMerchantPayout($dukandarUserId, $due, $path, $note, $scanId);
    }

    public function submitProofForOpen(int $dukandarUserId, string $path, string $note = '', ?float $amount = null): int
    {
        $shop = $this->requireApprovedShop($dukandarUserId);
        $id   = (int) $shop['id'];
        $due  = 0.0;
        foreach ($this->fifoUnpaidQueue($id) as $row) {
            $due += (float) $row['remaining'];
        }
        $pay = $amount === null ? $due : round($amount, 2);
        $res = $this->applyMerchantPayout($dukandarUserId, $pay, $path, $note);

        return count($res['lines']);
    }

    public function adminQueue(string $status = 'processing'): array
    {
        $builder = $this->scans
            ->select('dukandar_scans.*, dukandar_profiles.shop_name, dukandar_profiles.city, users.name as customer_name, users.phone as customer_phone')
            ->join('dukandar_profiles', 'dukandar_profiles.id = dukandar_scans.dukandar_id')
            ->join('users', 'users.id = dukandar_scans.customer_user_id', 'left');
        if ($status !== '' && $status !== 'all') {
            $builder->where('dukandar_scans.status', $status);
        }

        return $builder->orderBy('dukandar_scans.id', 'DESC')->findAll(120);
    }

    public function release(int $scanId, int $adminId, string $note = ''): void
    {
        $row = $this->scans->find($scanId);
        if (! $row) {
            throw new RuntimeException('Sale not found.');
        }
        if ($row['status'] === 'released') {
            return;
        }
        if (trim((string) ($row['proof_path'] ?? '')) === '' && (float) ($row['queued_amount'] ?? 0) <= 0) {
            throw new RuntimeException('Approve only after the merchant uploads a payment screenshot.');
        }
        $amount = max(0.0, round((float) $row['credit_amount'] - (float) ($row['paid_amount'] ?? 0), 2));
        if ($amount <= 0) {
            $this->scans->update($scanId, [
                'status'        => 'released',
                'queued_amount' => 0,
                'released_at'   => date('Y-m-d H:i:s'),
            ]);

            return;
        }
        $ok     = $this->wallet->credit(
            (int) $row['customer_user_id'],
            $amount,
            'solqam_card',
            $scanId,
            'Solqam Card 5% from shop sale #' . $scanId
        );
        if (! $ok) {
            throw new RuntimeException('Wallet credit failed.');
        }
        $this->scans->update($scanId, [
            'status'        => 'released',
            'released_at'   => date('Y-m-d H:i:s'),
            'paid_amount'   => (float) $row['credit_amount'],
            'queued_amount' => 0,
            'admin_note'    => $note !== '' ? $note : ('released_by_' . $adminId),
        ]);
        $user = (new UserModel())->find((int) $row['customer_user_id']);
        try {
            SmsNotifier::send(
                (string) ($user['phone'] ?? ''),
                'Solqam Card: Rs. ' . number_format($amount, 0) . ' is now in your wallet. Spend it at checkout or Solqam services.',
                'card_credit_released'
            );
        } catch (\Throwable $e) {
        }
    }

    public function reject(int $scanId, string $reason): void
    {
        $row = $this->scans->find($scanId);
        if (! $row) {
            throw new RuntimeException('Sale not found.');
        }
        if ($row['status'] === 'released') {
            throw new RuntimeException('Already released. Cannot reject.');
        }
        $this->scans->update($scanId, [
            'status'      => 'rejected',
            'rejected_at' => date('Y-m-d H:i:s'),
            'admin_note'  => $reason,
        ]);
    }

    public function setApproval(int $profileId, string $status, string $reason = ''): void
    {
        if (! in_array($status, ['approved', 'rejected', 'pending'], true)) {
            throw new RuntimeException('Invalid status.');
        }
        $this->shops->update($profileId, [
            'approval_status'  => $status,
            'rejection_reason' => $status === 'rejected' ? $reason : null,
        ]);
    }

    public function extractCardCode(string $raw): string
    {
        $raw = trim($raw);
        if (preg_match('#(?:card/c/|card_code=|code=)([A-Za-z0-9\-]+)#i', $raw, $m)) {
            $raw = $m[1];
        }
        $id = SolqamCardNumber::normalize($raw);
        if ($id !== '') {
            return $id;
        }
        $raw = strtoupper(trim($raw));
        if (preg_match('/SQC-[A-Z0-9]{6,12}/', $raw, $m)) {
            return $m[0];
        }
        $digitsOnly = preg_replace('/\D+/', '', $raw) ?? '';
        if (strlen($digitsOnly) >= 16) {
            if (preg_match('/8808\d{12}/', $digitsOnly, $m)) {
                return $m[0];
            }
            if (strlen($digitsOnly) === 16) {
                return $digitsOnly;
            }
        }
        $digits = preg_replace('/[^A-Z0-9]/', '', $raw) ?? '';
        if (str_starts_with($digits, 'SQC') && strlen($digits) >= 9) {
            return 'SQC-' . substr($digits, 3, 8);
        }

        return '';
    }

    public function formatPan(string $pan): string
    {
        $id = SolqamCardNumber::normalize($pan);
        if ($id !== '') {
            return SolqamCardNumber::display($id);
        }
        $pan = preg_replace('/\D+/', '', $pan) ?? '';

        return trim(chunk_split($pan, 4, ' '));
    }

    public function maskPan(string $pan): string
    {
        $id = SolqamCardNumber::normalize($pan);
        if ($id !== '') {
            $parts = SolqamCardNumber::decode($id);

            return ($parts['division_code'] ?: $id[0]) . '-••-' . ($parts['type'] ?? 'C') . '-••••' . substr((string) ($parts['unique'] ?? $id), -4);
        }
        $pan = preg_replace('/\D+/', '', $pan) ?? '';
        if (strlen($pan) < 8) {
            return '•••• •••• •••• ••••';
        }

        return substr($pan, 0, 4) . ' •••• •••• ' . substr($pan, -4);
    }

    protected function findCard(string $raw): ?array
    {
        $token = $this->extractCardCode($raw);
        if ($token === '') {
            return null;
        }
        $byPan = $this->cards->byPan($token);
        if ($byPan) {
            return $byPan;
        }
        if (preg_match('/^\d{16}$/', $token)) {
            return $this->cards->byCode($token);
        }

        return $this->cards->byCode($token);
    }

    protected function hydrateCard(array $card, int $userId): array
    {
        if ($card === [] || empty($card['id'])) {
            return $card;
        }
        $patch = [];
        if (! SolqamCardNumber::isValid((string) ($card['pan'] ?? ''))) {
            $pan = $this->mintPan((int) $card['id'], $userId);
            $patch['pan']   = $pan;
            $patch['last4'] = substr($pan, -4);
            $card['pan']    = $pan;
            $card['last4']  = $patch['last4'];
        }
        if (empty($card['cvv'])) {
            $patch['cvv'] = str_pad((string) random_int(0, 999), 3, '0', STR_PAD_LEFT);
            $card['cvv']  = $patch['cvv'];
        }
        if (empty($card['expires_at']) || $card['expires_at'] === '0000-00-00') {
            $issued = $card['issued_at'] ?? date('Y-m-d');
            $patch['expires_at'] = date('Y-m-d', strtotime($issued . ' +5 years'));
            $card['expires_at']  = $patch['expires_at'];
        }
        if (empty($card['holder_name'])) {
            $user = (new UserModel())->find($userId);
            $patch['holder_name'] = strtoupper((string) ($user['name'] ?? 'SOLQAM MEMBER'));
            $card['holder_name']  = $patch['holder_name'];
        }
        if ($patch !== []) {
            try {
                $this->cards->update((int) $card['id'], $patch);
            } catch (\Throwable $e) {
            }
        }
        $pan = (string) ($card['pan'] ?? '');
        $exp = (string) ($card['expires_at'] ?? '');
        $card['pan_grouped']  = $this->formatPan($pan);
        $card['pan_masked']   = $this->maskPan($pan);
        $card['id_parts']     = SolqamCardNumber::decode($pan);
        $unique               = (string) ($card['id_parts']['unique'] ?? '');
        $card['last4']        = $unique !== '' ? substr($unique, -4) : substr($pan, -4);
        $card['expires_fmt']  = $exp !== '' ? date('m/y', strtotime($exp)) : '—';
        $card['member_since'] = ! empty($card['issued_at']) ? date('m/y', strtotime((string) $card['issued_at'])) : date('m/y');
        $card['is_frozen']    = ($card['status'] ?? '') === 'frozen' || ! empty($card['frozen_at']);
        $card['has_pin']      = ! empty($card['pin_hash']);
        $card['network']      = 'SOLQAM';
        $card['product']      = 'Marketplace Credit';
        $compact              = SolqamCardNumber::normalize($pan) ?: $pan;
        $card['share_url']    = $compact !== '' ? site_url('card/c/' . $compact) : '';
        $card['qr']           = $card['share_url'] !== '' ? $card['share_url'] : SolqamCardNumber::display($pan);

        return $card;
    }

    protected function mintPan(int $cardId, int $userId, ?string $forcedSerial = null): string
    {
        $user = (new UserModel())->find($userId) ?: [];
        $shop = $this->profileForUser($userId);
        $type = SolqamCardNumber::typeForUser($user, (bool) $shop);

        return SolqamCardNumber::mintFromGeo($user, $type, $shop, $forcedSerial);
    }

    public function remintAsMerchant(int $userId): void
    {
        $card = $this->cards->forUser($userId);
        if (! $card) {
            $this->issueCard($userId);

            return;
        }
        $pan = $this->mintPan((int) $card['id'], $userId);
        $this->cards->update((int) $card['id'], [
            'pan'   => $pan,
            'last4' => substr($pan, -4),
        ]);
    }

    public function refreshMerchantCategoryPan(int $userId): void
    {
        $card = $this->cards->forUser($userId);
        if (! $card) {
            return;
        }
        $parts  = SolqamCardNumber::decode((string) ($card['pan'] ?? ''));
        $serial = (string) ($parts['unique'] ?? '');
        if (($parts['type'] ?? '') !== 'M' || $serial === '') {
            $this->remintAsMerchant($userId);

            return;
        }
        $user = (new UserModel())->find($userId) ?: [];
        $shop = $this->profileForUser($userId);
        $pan  = SolqamCardNumber::mintFromGeo($user, 'M', $shop, $serial);
        $this->cards->update((int) $card['id'], [
            'pan'   => $pan,
            'last4' => substr($pan, -4),
        ]);
    }

    public function assignGoldenNumber(int $userId, int $goldenId): string
    {
        $goldModel = new \App\Models\SolqamGoldenNumberModel();
        $gold      = $goldModel->find($goldenId);
        if (! $gold) {
            throw new RuntimeException('Golden number not found.');
        }
        if (($gold['status'] ?? '') === 'assigned') {
            throw new RuntimeException('This golden number is already assigned.');
        }
        $serial = (string) $gold['serial'];
        $type   = strtoupper((string) $gold['member_type']);
        $width  = $type === 'M' ? 7 : 8;
        $serial = str_pad(preg_replace('/\D+/', '', $serial) ?? '', $width, '0', STR_PAD_LEFT);
        $used   = $this->cards->findAll(5000);
        foreach ($used as $row) {
            $parts = SolqamCardNumber::decode((string) ($row['pan'] ?? ''));
            if (SolqamGeoCatalog::memberType((string) ($parts['type'] ?? '')) === ($type === 'M' ? 'M' : 'C')
                && (string) ($parts['unique'] ?? '') === $serial
                && (int) ($row['user_id'] ?? 0) !== $userId
            ) {
                throw new RuntimeException('That serial is already on another Solqam Card.');
            }
        }
        $this->issueCard($userId);
        $card = $this->cards->forUser($userId);
        if (! $card) {
            throw new RuntimeException('Could not issue a Solqam Card for this member.');
        }
        $user = (new UserModel())->find($userId) ?: [];
        $shop = $this->profileForUser($userId);
        $want = strtoupper((string) $gold['member_type']);
        $have = SolqamCardNumber::typeForUser($user, (bool) $shop);
        if ($want === 'M' && ! $shop) {
            throw new RuntimeException('Assign a merchant golden number only to a registered merchant.');
        }
        if ($want === 'C' && $have === 'M') {
            throw new RuntimeException('This is a customer golden number. Pick a merchant golden number for this shop.');
        }
        $pan = SolqamCardNumber::mintFromGeo($user, $want === 'M' ? 'M' : $have, $shop, (string) $gold['serial']);
        $this->cards->update((int) $card['id'], [
            'pan'   => $pan,
            'last4' => substr($pan, -4),
        ]);
        $goldModel->update($goldenId, [
            'status'  => 'assigned',
            'user_id' => $userId,
            'card_id' => (int) $card['id'],
        ]);

        return SolqamCardNumber::display($pan);
    }

    protected function userGeo(int $userId): array
    {
        try {
            $user = (new UserModel())->find($userId);
            if ($user && trim((string) ($user['city'] ?? '')) !== '') {
                return [
                    'city'     => (string) $user['city'],
                    'province' => (string) ($user['province'] ?? ''),
                ];
            }
        } catch (\Throwable $e) {
        }
        try {
            $addr = (new \App\Models\AddressModel())->getDefaultAddress($userId);
            if ($addr && trim((string) ($addr['city'] ?? '')) !== '') {
                return [
                    'city'     => (string) $addr['city'],
                    'province' => (string) ($addr['province'] ?? ''),
                ];
            }
        } catch (\Throwable $e) {
        }
        $shop = $this->profileForUser($userId);
        if ($shop) {
            return [
                'city'     => (string) ($shop['city'] ?? ''),
                'province' => '',
            ];
        }
        try {
            $seller = (new \App\Models\SellerProfileModel())->getByUserId($userId);
            if ($seller) {
                return [
                    'city'     => (string) ($seller['city'] ?? ''),
                    'province' => '',
                ];
            }
        } catch (\Throwable $e) {
        }

        return ['city' => 'Abbottabad', 'province' => 'KPK'];
    }

    protected function luhnCheckDigit(string $number): string
    {
        $sum    = 0;
        $alt    = true;
        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $n = (int) $number[$i];
            if ($alt) {
                $n *= 2;
                if ($n > 9) {
                    $n -= 9;
                }
            }
            $sum += $n;
            $alt = ! $alt;
        }

        return (string) ((10 - ($sum % 10)) % 10);
    }

    protected function assertCvv(array $card, string $cvv): void
    {
        $cvv = preg_replace('/\D+/', '', $cvv) ?? '';
        if ($cvv === '' || ! hash_equals((string) ($card['cvv'] ?? ''), $cvv)) {
            throw new RuntimeException('CVV does not match this Solqam Card.');
        }
    }

    protected function assertPin(array $card, string $pin): void
    {
        $pin = preg_replace('/\D+/', '', $pin) ?? '';
        if ($pin === '' || empty($card['pin_hash']) || ! password_verify($pin, (string) $card['pin_hash'])) {
            throw new RuntimeException('Shop PIN is required and must match the customer card.');
        }
    }

    public function maskPhone(string $phone): string
    {
        $d = preg_replace('/\D+/', '', $phone) ?? '';
        if (strlen($d) < 6) {
            return '—';
        }

        return substr($d, 0, 4) . '••••' . substr($d, -3);
    }

    public static function normalizeOccupancy($raw): ?string
    {
        $v = strtolower(trim((string) $raw));
        if (in_array($v, ['owner', 'owned'], true)) {
            return 'owner';
        }
        if (in_array($v, ['rent', 'rented', 'rental', 'tenant'], true)) {
            return 'rent';
        }

        return null;
    }

    public static function normalizeBusinessYear($raw): ?int
    {
        $y   = (int) preg_replace('/[^0-9]/', '', (string) $raw);
        $max = (int) date('Y');
        if ($y < 1900 || $y > $max) {
            return null;
        }

        return $y;
    }

    protected function requireApprovedShop(int $userId): array
    {
        $shop = $this->profileForUser($userId);
        if (! $shop) {
            throw new RuntimeException('Open a merchant profile first.');
        }
        if (($shop['approval_status'] ?? '') !== 'approved') {
            throw new RuntimeException('Solqam must approve your shop before you can scan cards.');
        }

        return $shop;
    }
}
