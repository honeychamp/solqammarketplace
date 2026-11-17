<?php

namespace App\Services\Catalog;

/**
 * Starter reviews so the storefront is not empty.
 * Tops every active product up to 11–12 reviews (keeps real customer reviews).
 * Includes Whole Trades. HTTP only fills a small batch so pages stay fast.
 */
class StarterReviewService
{
    public const TARGET_MIN = 11;
    public const TARGET_MAX = 12;

    /**
     * Cheap HTTP fill: this product (if short) plus a small newest-short batch.
     */
    public static function touchStorefront(?int $productId = null): void
    {
        if (is_cli()) {
            return;
        }
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if ($method !== 'GET' && $method !== 'HEAD') {
            return;
        }
        static $ran = false;
        if ($ran) {
            return;
        }
        $ran = true;

        try {
            $db = \Config\Database::connect();
            if (! $db->tableExists('reviews') || ! $db->tableExists('products') || ! $db->tableExists('users')) {
                return;
            }
            if ($productId && $productId > 0) {
                self::seedProducts($db, [['id' => $productId]]);
            }

            $cacheDir = rtrim(WRITEPATH, '\\/') . DIRECTORY_SEPARATOR . 'cache';
            if (! is_dir($cacheDir)) {
                @mkdir($cacheDir, 0755, true);
            }
            $tick = $cacheDir . DIRECTORY_SEPARATOR . 'starter_reviews_tick';
            if (is_file($tick) && (time() - (int) filemtime($tick)) < 480) {
                return;
            }
            @touch($tick);
            self::fillShortBatch($db, 12);
        } catch (\Throwable $e) {
            self::rememberError($e->getMessage());
        }
    }

    /**
     * @return array{products:int, reviews:int}
     */
    public static function ensure(bool $force = false): array
    {
        if (! is_cli()) {
            return ['products' => 0, 'reviews' => 0];
        }
        static $ran = false;
        if ($ran && ! $force) {
            return ['products' => 0, 'reviews' => 0];
        }
        $ran = true;

        $cacheDir = rtrim(WRITEPATH, '\\/') . DIRECTORY_SEPARATOR . 'cache';
        if (! is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        $tick = $cacheDir . DIRECTORY_SEPARATOR . 'starter_reviews_tick';

        $totals = ['products' => 0, 'reviews' => 0];

        try {
            $db = \Config\Database::connect();
            if (! $db->tableExists('reviews') || ! $db->tableExists('products') || ! $db->tableExists('users')) {
                return $totals;
            }

            if (! $force && is_file($tick) && (time() - (int) filemtime($tick)) < 3600) {
                return $totals;
            }
            @touch($tick);

            $rounds    = 0;
            $maxRounds = $force ? 80 : 1;

            do {
                $batch = self::fillShortBatch($db, 80);
                $totals['products'] += $batch['products'];
                $totals['reviews']  += $batch['reviews'];
                if ($batch['products'] === 0) {
                    break;
                }
                $rounds++;
            } while ($force && $rounds < $maxRounds);
        } catch (\Throwable $e) {
            self::rememberError($e->getMessage());
            log_message('error', 'Starter reviews: ' . $e->getMessage());
        }

        return $totals;
    }

    /**
     * @return array{products:int, reviews:int}
     */
    protected static function fillShortBatch($db, int $limit): array
    {
        $cap = max(1, min(80, $limit));
        $min = self::TARGET_MIN;
        $products = $db->query(
            "SELECT p.id FROM products p
             WHERE p.status = ?
               AND (
                    SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.id
               ) < ?
             ORDER BY p.id DESC
             LIMIT {$cap}",
            ['active', $min]
        )->getResultArray();

        return self::seedProducts($db, $products);
    }

    /**
     * @param list<array{id?:int}> $products
     * @return array{products:int, reviews:int}
     */
    protected static function seedProducts($db, array $products): array
    {
        $totals = ['products' => 0, 'reviews' => 0];
        if ($products === []) {
            return $totals;
        }
        $reviewers = self::reviewers($db);
        if ($reviewers === []) {
            self::rememberError('No reviewer user ids available.');
            return $totals;
        }

        $comments = self::comments();
        $now      = time();
        $fields   = $db->getFieldNames('reviews');
        $has      = static fn (string $col): bool => in_array($col, $fields, true);
        $revCount = count($reviewers);
        $comCount = count($comments);

        foreach ($products as $i => $product) {
            $pid = (int) ($product['id'] ?? 0);
            if ($pid < 1) {
                continue;
            }
            $have = (int) $db->table('reviews')->where('product_id', $pid)->countAllResults();
            $want = self::TARGET_MIN + ($pid % 2); // 11 or 12
            $need = $want - $have;
            if ($need < 1) {
                continue;
            }

            $used = [];
            $usedRows = $db->table('reviews')->select('user_id')->where('product_id', $pid)->get()->getResultArray();
            foreach ($usedRows as $usedRow) {
                $used[(int) $usedRow['user_id']] = true;
            }

            $added = 0;
            $guard = 0;
            while ($added < $need && $guard < 40) {
                $guard++;
                $user = $reviewers[($pid + $have + $added + $guard) % $revCount];
                $uid  = (int) $user['id'];
                if (isset($used[$uid]) && count($used) < $revCount) {
                    continue;
                }
                $used[$uid] = true;
                $row = [];
                if ($has('product_id')) {
                    $row['product_id'] = $pid;
                }
                if ($has('user_id')) {
                    $row['user_id'] = $uid;
                }
                if ($has('rating')) {
                    $row['rating'] = (($pid + $added) % 6 === 0) ? 4 : 5;
                }
                if ($has('comment')) {
                    $row['comment'] = $comments[($pid + $have + $added) % $comCount];
                }
                if ($has('is_approved')) {
                    $row['is_approved'] = 1;
                }
                $created = date('Y-m-d H:i:s', $now - (($i * 9 + $added * 13 + $pid) % 40) * 86400 - ($added * 2700));
                if ($has('created_at')) {
                    $row['created_at'] = $created;
                }
                if ($has('updated_at')) {
                    $row['updated_at'] = $created;
                }
                $ok = $db->table('reviews')->insert($row);
                if ($ok === false) {
                    $err = $db->error();
                    self::rememberError('Insert failed: ' . ($err['message'] ?? 'unknown'));

                    return $totals;
                }
                $added++;
                $totals['reviews']++;
            }
            if ($added > 0) {
                $totals['products']++;
            }
        }

        return $totals;
    }

    /**
     * @return list<array{id:int}>
     */
    protected static function reviewers($db): array
    {
        $people = [
            ['Ahmed Khan', 'ahmed.khan'],
            ['Fatima Ali', 'fatima.ali'],
            ['Usman Raza', 'usman.raza'],
            ['Ayesha Malik', 'ayesha.malik'],
            ['Hamza Sheikh', 'hamza.sheikh'],
            ['Sana Iqbal', 'sana.iqbal'],
            ['Bilal Ahmed', 'bilal.ahmed'],
            ['Zara Hussain', 'zara.hussain'],
            ['Omar Farooq', 'omar.farooq'],
            ['Hina Shah', 'hina.shah'],
            ['Ali Hassan', 'ali.hassan'],
            ['Maryam Noor', 'maryam.noor'],
            ['Hassan Javed', 'hassan.javed'],
            ['Iqra Nadeem', 'iqra.nadeem'],
            ['Tariq Mehmood', 'tariq.mehmood'],
            ['Nimra Khalid', 'nimra.khalid'],
        ];
        $ids = [];
        $now = date('Y-m-d H:i:s');
        $userFields = $db->getFieldNames('users');
        $has = static fn (string $col): bool => in_array($col, $userFields, true);

        foreach ($people as $i => $person) {
            $email = $person[1] . '@reviews.solqam.local';
            $row = $db->table('users')->select('id')->where('email', $email)->get()->getRowArray();
            if ($row) {
                $ids[] = ['id' => (int) $row['id']];
                continue;
            }
            $insert = [
                'name'  => $person[0],
                'email' => $email,
            ];
            if ($has('created_at')) {
                $insert['created_at'] = $now;
            }
            if ($has('updated_at')) {
                $insert['updated_at'] = $now;
            }
            if ($has('phone')) {
                $insert['phone'] = 'oauth:rev' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT);
            }
            if ($has('role')) {
                $insert['role'] = 'customer';
            }
            if ($has('status')) {
                $insert['status'] = 'active';
            }
            if ($has('is_verified')) {
                $insert['is_verified'] = 1;
            }
            if ($has('password_hash')) {
                $insert['password_hash'] = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
            }
            if ($has('deleted_at')) {
                $insert['deleted_at'] = null;
            }

            $ok = $db->table('users')->insert($insert);
            $id = (int) $db->insertID();
            if ($ok !== false && $id > 0) {
                $ids[] = ['id' => $id];
            }
        }

        if ($ids !== []) {
            return $ids;
        }

        $fallback = $db->table('users')->select('id')->orderBy('id', 'ASC')->limit(16)->get()->getResultArray();
        foreach ($fallback as $row) {
            $ids[] = ['id' => (int) $row['id']];
        }

        return $ids;
    }

    protected static function rememberError(string $message): void
    {
        $path = rtrim(WRITEPATH, '\\/') . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'starter_reviews_last_error.txt';
        @file_put_contents($path, date('c') . ' ' . $message);
        log_message('error', 'Starter reviews: ' . $message);
    }

    /**
     * @return list<string>
     */
    protected static function comments(): array
    {
        return [
            'Received on time. Item matches the photos. Happy with this order.',
            'Good quality for the price. Packing was neat and the courier was fine.',
            'As described. Using it for a week now and no issues.',
            'Fast delivery to my city. Would buy from this store again.',
            'Looks better in person. Stitching / finish is clean.',
            'Value for money on Solqam. Checkout and COD were easy.',
            'Exactly what I ordered. No missing parts, box was intact.',
            'Solid product. Battery / build feels better than I expected.',
            'Colour and size are accurate. Recommended.',
            'Smooth order. Seller packed it well. Five stars.',
            'Works as expected. Support replied when I asked about delivery.',
            'Nice quality. Gifted one and they liked it too.',
            'Second order from this listing. Same quality as the first time.',
            'Packed well, no scratches. Installed / used the same evening.',
            'Better than similar items I saw in the market. Fair price.',
            'Arrived a day early. Description is honest, no surprises.',
        ];
    }
}
