<?php

namespace App\Services\Catalog;

/**
 * Starter reviews so the storefront is not empty.
 * Fills active products that still have zero reviews, newest first.
 * Runs in chunks so a slow host does not time out on the first hit.
 */
class StarterReviewService
{
    public static function ensure(): void
    {
        // Never run on HTTP. Live storefront was spending 20–30s per page on
        // DELETE/INSERT starter reviews. Seed only from CLI (spark) if needed.
        if (! is_cli()) {
            return;
        }
        static $ran = false;
        if ($ran) {
            return;
        }
        $ran = true;

        $cacheDir = rtrim(WRITEPATH, '\\/') . DIRECTORY_SEPARATOR . 'cache';
        if (! is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        $tick = $cacheDir . DIRECTORY_SEPARATOR . 'starter_reviews_tick';

        try {
            $db = \Config\Database::connect();
            if (! $db->tableExists('reviews') || ! $db->tableExists('products') || ! $db->tableExists('users')) {
                return;
            }

            if (is_file($tick) && (time() - (int) filemtime($tick)) < 3600) {
                return;
            }
            @touch($tick);

            self::stripExcludedStarterReviews($db);

            $reviewers = self::reviewers($db);
            if ($reviewers === []) {
                self::rememberError('No reviewer user ids available.');
                return;
            }

            $comments = self::comments();
            $now      = time();
            $inserted = 0;

            $skip  = self::excludedSellerSql($db);
            $joins = self::sellerJoins($db);
            $products = $db->query(
                "SELECT p.id FROM products p
                 {$joins}
                 WHERE p.status = ?
                   AND NOT EXISTS (SELECT 1 FROM reviews r WHERE r.product_id = p.id)
                   AND NOT {$skip}
                 ORDER BY p.id DESC
                 LIMIT 80",
                ['active']
            )->getResultArray();

            if ($products === []) {
                return;
            }

            $fields = $db->getFieldNames('reviews');
            $has    = static fn (string $col): bool => in_array($col, $fields, true);

            foreach ($products as $i => $product) {
                $pid = (int) $product['id'];
                $n   = 3 + ($i % 3);
                for ($k = 0; $k < $n; $k++) {
                    $user = $reviewers[($i + $k) % count($reviewers)];
                    $row  = [];
                    if ($has('product_id')) {
                        $row['product_id'] = $pid;
                    }
                    if ($has('user_id')) {
                        $row['user_id'] = (int) $user['id'];
                    }
                    if ($has('rating')) {
                        $row['rating'] = ($k === 2 && ($i % 5 === 0)) ? 4 : 5;
                    }
                    if ($has('comment')) {
                        $row['comment'] = $comments[($i + $k) % count($comments)];
                    }
                    if ($has('is_approved')) {
                        $row['is_approved'] = 1;
                    }
                    $created = date('Y-m-d H:i:s', $now - (($i * 11 + $k * 17) % 18) * 86400 - ($k * 3600));
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
                        return;
                    }
                    $inserted++;
                }
            }

            // Tick already stamped at start — at most one seed pass per hour.
        } catch (\Throwable $e) {
            self::rememberError($e->getMessage());
            log_message('error', 'Starter reviews: ' . $e->getMessage());
        }
    }

    /**
     * Whole Traders (and similar store/account names) must not get starter reviews.
     */
    protected static function excludedSellerSql($db): string
    {
        $norm = static fn (string $col): string => "REPLACE(REPLACE(LOWER(COALESCE({$col}, '')), ' ', ''), '-', '')";
        $parts = [
            $norm('u.name') . " LIKE '%wholetraders%'",
            $norm('u.email') . " LIKE '%wholetraders%'",
        ];
        if ($db->tableExists('seller_profiles')) {
            $parts[] = $norm('sp.store_name') . " LIKE '%wholetraders%'";
            $fields = $db->getFieldNames('seller_profiles');
            if (in_array('business_name', $fields, true)) {
                $parts[] = $norm('sp.business_name') . " LIKE '%wholetraders%'";
            }
        }

        return '(' . implode(' OR ', $parts) . ')';
    }

    /**
     * Remove fake starter reviews already written on excluded sellers.
     * Real customer reviews (other emails) are left untouched.
     */
    protected static function stripExcludedStarterReviews($db): void
    {
        $skip  = self::excludedSellerSql($db);
        $joins = self::sellerJoins($db);
        $db->query(
            "DELETE r FROM reviews r
             INNER JOIN products p ON p.id = r.product_id
             {$joins}
             INNER JOIN users reviewer ON reviewer.id = r.user_id
             WHERE reviewer.email LIKE '%@reviews.solqam.local'
               AND {$skip}"
        );
    }

    protected static function sellerJoins($db): string
    {
        $sql = 'LEFT JOIN users u ON u.id = p.seller_id';
        if ($db->tableExists('seller_profiles')) {
            $sql .= ' LEFT JOIN seller_profiles sp ON sp.user_id = p.seller_id';
        }

        return $sql;
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

        $fallback = $db->table('users')->select('id')->orderBy('id', 'ASC')->limit(12)->get()->getResultArray();
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
        ];
    }
}
