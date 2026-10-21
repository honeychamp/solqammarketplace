<?php

namespace App\Services\Marketing;

use App\Models\CategoryModel;
use App\Models\FlashSaleItemModel;
use App\Models\FlashSaleModel;
use DateTime;
use DateTimeZone;

class MetaCatalogFeedService
{
    public const HEADERS = [
        'id',
        'title',
        'description',
        'availability',
        'condition',
        'price',
        'link',
        'image_link',
        'brand',
        'sale_price',
        'sale_price_effective_date',
        'additional_image_link',
        'video[0].url',
        'product_type',
        'google_product_category',
        'fb_product_category',
        'item_group_id',
        'color',
        'size',
        'material',
        'pattern',
        'gender',
        'age_group',
        'quantity_to_sell_on_facebook',
        'status',
        'custom_label_0',
        'custom_label_1',
        'custom_label_2',
        'custom_label_3',
        'custom_label_4',
        'product_tags[0]',
        'product_tags[1]',
    ];

    public function buildCsv(): string
    {
        helper('marketplace');
        $cache  = cache();
        $cached = $cache->get('meta_catalog_csv_v1');
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $sales = $this->salesLast30Days();
        $rows  = $this->buildRows($sales);

        $fh = fopen('php://temp', 'r+');
        fprintf($fh, "\xEF\xBB\xBF");
        fputcsv($fh, self::HEADERS);
        $seen = [];
        foreach ($rows as $row) {
            $id = (string) ($row['id'] ?? '');
            if ($id === '' || isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $ordered = [];
            foreach (self::HEADERS as $col) {
                $ordered[] = $row[$col] ?? '';
            }
            fputcsv($fh, $ordered);
        }
        rewind($fh);
        $csv = stream_get_contents($fh) ?: '';
        fclose($fh);

        $cache->save('meta_catalog_csv_v1', $csv, 300);

        return $csv;
    }

    /**
     * @param array<int,int> $sales product_id => qty
     * @return list<array<string,string|int>>
     */
    protected function buildRows(array $sales): array
    {
        $db = \Config\Database::connect();
        $products = $this->loadProducts($db);
        if ($products === []) {
            return [];
        }

        $ids      = array_map(static fn ($p) => (int) $p['id'], $products);
        $variants = $this->loadVariants($db, $ids);
        $media    = $this->loadMedia($db, $ids);
        $flash    = $this->loadFlashMeta();
        $catModel = new CategoryModel();

        $ranked = [];
        foreach ($products as $p) {
            $ranked[(int) $p['id']] = (int) ($sales[(int) $p['id']] ?? 0);
        }
        arsort($ranked);
        $withSales = array_filter($ranked, static fn ($q) => $q > 0);
        $topCut    = max(1, (int) ceil(count($products) * 0.10));
        $topIds    = array_slice(array_keys($withSales), 0, $topCut, true);
        $topSet    = array_flip($topIds);

        $out = [];
        foreach ($products as $product) {
            $pid = (int) $product['id'];
            $vars = $variants[$pid] ?? [];
            if ($vars === []) {
                $row = $this->rowFor($product, null, $media[$pid] ?? [], $flash[$pid] ?? null, $catModel, $sales, $topSet);
                if ($row) {
                    $out[] = $row;
                }
                continue;
            }
            foreach ($vars as $variant) {
                $row = $this->rowFor($product, $variant, $media[$pid] ?? [], $flash[$pid] ?? null, $catModel, $sales, $topSet);
                if ($row) {
                    $out[] = $row;
                }
            }
        }

        return $out;
    }

    protected function rowFor(array $product, ?array $variant, array $media, ?array $flash, CategoryModel $catModel, array $sales, array $topSet): ?array
    {
        $pid = (int) $product['id'];
        $vid = $variant ? (int) ($variant['id'] ?? 0) : 0;
        $id  = catalog_content_id($pid, $vid ?: null);

        $images = $media['images'] ?? [];
        $videos = $media['videos'] ?? [];
        $main   = '';
        if ($variant) {
            $vimg = trim((string) ($variant['image_path'] ?? ''));
            if ($vimg !== '') {
                $main = $this->httpsUrl(media_url($vimg));
            }
        }
        if ($main === '' && $images !== []) {
            $main = $this->httpsUrl(media_url((string) $images[0]));
        }
        if ($main === '') {
            return null;
        }

        $basePrice = $variant && $variant['price'] !== null && (float) $variant['price'] > 0
            ? (float) $variant['price']
            : (float) ($product['price'] ?? 0);
        $current = $basePrice;
        if ($flash && (float) $flash['price'] > 0) {
            $current = min($current, (float) $flash['price']);
        }
        $compare = (float) ($product['compare_at_price'] ?? 0);
        $list    = $compare > $current ? $compare : (($flash && $basePrice > $current) ? $basePrice : $current);
        if ($list <= 0) {
            return null;
        }

        $salePrice = '';
        $saleDates = '';
        if ($current > 0 && $current < $list - 0.009) {
            $salePrice = $this->money($current);
            if ($flash && ! empty($flash['starts']) && ! empty($flash['ends'])) {
                $saleDates = $this->isoRange((string) $flash['starts'], (string) $flash['ends']);
            }
        }

        $stock = $variant ? (int) ($variant['stock'] ?? 0) : (int) ($product['stock'] ?? 0);
        $color = trim((string) ($variant['color'] ?? ''));
        $size  = trim((string) ($variant['size'] ?? ''));
        $specs = $this->parseSpecs((string) ($product['specifications'] ?? ''));
        $title = $this->title($product, $color, $size);
        $desc  = $this->description($product, $title, $specs);
        $path  = $this->productType($catModel, (int) ($product['category_id'] ?? 0), (string) ($product['category_name'] ?? ''));
        $root  = $this->rootLabel($path);
        $qty30 = (int) ($sales[$pid] ?? 0);
        $disc  = $salePrice !== '' ? (($list - $current) / $list) * 100 : 0;
        $created = (string) ($product['created_at'] ?? '');

        $extraImages = [];
        foreach (array_slice($images, 1, 12) as $img) {
            $url = $this->httpsUrl(media_url((string) $img));
            if ($url !== '' && $url !== $main) {
                $extraImages[] = $url;
            }
        }
        $extraImages = $this->lifestyleFirst($extraImages);
        $extraImages = array_slice($extraImages, 0, 10);

        $videoUrl = '';
        if ($videos !== []) {
            $videoUrl = $this->httpsUrl(media_url((string) $videos[0]));
        }

        $gender = $this->genderFrom($path . ' ' . $title);
        $age    = $this->ageFrom($path . ' ' . $title);

        return [
            'id'                           => $id,
            'title'                        => $title,
            'description'                  => $desc,
            'availability'                 => $stock > 0 ? 'in stock' : 'out of stock',
            'condition'                    => 'new',
            'price'                        => $this->money($list),
            'link'                         => $this->httpsUrl(site_url('product/' . $pid)),
            'image_link'                   => $main,
            'brand'                        => $this->brand($product),
            'sale_price'                   => $salePrice,
            'sale_price_effective_date'    => $saleDates,
            'additional_image_link'        => implode(',', $extraImages),
            'video[0].url'                 => $videoUrl,
            'product_type'                 => $path,
            'google_product_category'      => $this->googleCategory($path),
            'fb_product_category'          => $this->fbCategory($path),
            'item_group_id'                => (string) $pid,
            'color'                        => $color,
            'size'                         => $size,
            'material'                     => (string) ($specs['material'] ?? $specs['Material'] ?? ''),
            'pattern'                      => (string) ($specs['pattern'] ?? $specs['Pattern'] ?? ''),
            'gender'                       => $gender,
            'age_group'                    => $age,
            'quantity_to_sell_on_facebook' => max(0, $stock),
            'status'                       => 'active',
            'custom_label_0'               => $root,
            'custom_label_1'               => $this->discountLabel($disc),
            'custom_label_2'               => $this->priceBand($current > 0 ? $current : $list),
            'custom_label_3'               => isset($topSet[$pid]) ? 'top_seller' : ($qty30 > 0 ? 'selling' : 'no_sales'),
            'custom_label_4'               => $stock > 0 && $stock < 10 ? 'stock_low' : 'stock_high',
            'product_tags[0]'              => $this->storeName($product),
            'product_tags[1]'              => $this->isNewArrival($created) ? 'new_arrival' : '',
        ];
    }

    protected function loadProducts($db): array
    {
        $builder = $db->table('products p')
            ->select('p.*, c.name as category_name, c.slug as category_slug, sp.store_name, sp.approval_status as seller_approval, u.name as seller_name')
            ->join('categories c', 'c.id = p.category_id', 'left')
            ->join('seller_profiles sp', 'sp.user_id = p.seller_id', 'left')
            ->join('users u', 'u.id = p.seller_id', 'left')
            ->where('p.status', 'active');
        try {
            if ($db->fieldExists('deleted_at', 'products')) {
                $builder->where('p.deleted_at', null);
            }
        } catch (\Throwable $e) {
        }
        try {
            if ($db->fieldExists('approval_status', 'seller_profiles')) {
                $builder->groupStart()
                    ->where('sp.approval_status', 'approved')
                    ->orWhere('sp.id', null)
                    ->groupEnd();
            }
        } catch (\Throwable $e) {
        }

        return $builder->orderBy('p.id', 'DESC')->get()->getResultArray();
    }

    protected function loadVariants($db, array $ids): array
    {
        $map = [];
        if ($ids === [] || ! $db->tableExists('product_variants')) {
            return $map;
        }
        foreach (array_chunk($ids, 800) as $chunk) {
            $rows = $db->table('product_variants')->whereIn('product_id', $chunk)->orderBy('id', 'ASC')->get()->getResultArray();
            foreach ($rows as $row) {
                $pid = (int) $row['product_id'];
                $row['price'] = isset($row['price']) && $row['price'] !== '' && $row['price'] !== null
                    ? (float) $row['price']
                    : null;
                $row['image_path'] = (string) ($row['image'] ?? '');
                $map[$pid][] = $row;
            }
        }

        return $map;
    }

    protected function loadMedia($db, array $ids): array
    {
        $map = [];
        if ($ids === [] || ! $db->tableExists('product_images')) {
            return $map;
        }
        $hasType = false;
        try {
            $hasType = $db->fieldExists('media_type', 'product_images');
        } catch (\Throwable $e) {
        }
        foreach (array_chunk($ids, 800) as $chunk) {
            $q = $db->table('product_images')->whereIn('product_id', $chunk);
            try {
                if ($db->fieldExists('sort_order', 'product_images')) {
                    $q->orderBy('is_primary', 'DESC')->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC');
                } else {
                    $q->orderBy('is_primary', 'DESC')->orderBy('id', 'ASC');
                }
            } catch (\Throwable $e) {
                $q->orderBy('id', 'ASC');
            }
            foreach ($q->get()->getResultArray() as $row) {
                $pid  = (int) $row['product_id'];
                $path = (string) ($row['image_path'] ?? '');
                if ($path === '') {
                    continue;
                }
                $type = $hasType ? (string) ($row['media_type'] ?? 'image') : 'image';
                if (function_exists('is_product_video') && is_product_video($path, $type)) {
                    $map[$pid]['videos'][] = $path;
                    continue;
                }
                $map[$pid]['images'][] = $path;
            }
        }

        return $map;
    }

    protected function loadFlashMeta(): array
    {
        $out = [];
        try {
            $campaigns = (new FlashSaleModel())->getLiveCampaigns();
            if ($campaigns === []) {
                return $out;
            }
            $byId = [];
            foreach ($campaigns as $c) {
                $byId[(int) $c['id']] = $c;
            }
            $items = (new FlashSaleItemModel())
                ->whereIn('flash_sale_id', array_keys($byId))
                ->where('status', 'approved')
                ->findAll();
            foreach ($items as $item) {
                $pid = (int) $item['product_id'];
                $price = (float) $item['sale_price'];
                $camp = $byId[(int) $item['flash_sale_id']] ?? null;
                if (! $camp) {
                    continue;
                }
                if (! isset($out[$pid]) || $price < (float) $out[$pid]['price']) {
                    $out[$pid] = [
                        'price'  => $price,
                        'starts' => (string) ($camp['starts_at'] ?? ''),
                        'ends'   => (string) ($camp['ends_at'] ?? ''),
                    ];
                }
            }
        } catch (\Throwable $e) {
        }

        return $out;
    }

    /**
     * @return array<int,int>
     */
    protected function salesLast30Days(): array
    {
        $cache = cache();
        $hit   = $cache->get('meta_feed_sales_30d');
        if (is_array($hit)) {
            return $hit;
        }
        $map = [];
        try {
            $db = \Config\Database::connect();
            if (! $db->tableExists('order_items') || ! $db->tableExists('orders')) {
                return $map;
            }
            $since = date('Y-m-d H:i:s', time() - 30 * 86400);
            $rows  = $db->table('order_items')
                ->select('order_items.product_id, SUM(order_items.quantity) as qty')
                ->join('orders', 'orders.id = order_items.order_id')
                ->where('orders.created_at >=', $since)
                ->whereNotIn('orders.status', ['cancelled', 'returned', 'undelivered'])
                ->groupBy('order_items.product_id')
                ->get()
                ->getResultArray();
            foreach ($rows as $row) {
                $map[(int) $row['product_id']] = (int) $row['qty'];
            }
        } catch (\Throwable $e) {
            log_message('error', 'Meta feed sales: ' . $e->getMessage());
        }
        $cache->save('meta_feed_sales_30d', $map, 2700);

        return $map;
    }

    protected function title(array $product, string $color, string $size): string
    {
        $brand = $this->brand($product);
        $name  = trim((string) ($product['name'] ?? ''));
        $title = $name;
        if ($brand !== '' && $brand !== 'Solqam' && stripos($name, $brand) === false) {
            $title = $brand . ' ' . $name;
        }
        $bits = array_filter([$color, $size]);
        if ($bits) {
            $title .= ' - ' . implode(', ', $bits);
        }
        $title = $this->plain($title);
        $title = preg_replace('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u', '', $title) ?? $title;
        $title = trim(preg_replace('/\s+/', ' ', $title) ?? $title);
        if ($title !== '' && $title === strtoupper($title) && preg_match('/[A-Z]/', $title)) {
            $title = $this->titleCase($title);
        }
        if (mb_strlen($title) > 150) {
            $title = rtrim(mb_substr($title, 0, 147)) . '...';
        }

        return $title !== '' ? $title : 'Solqam Product';
    }

    protected function description(array $product, string $title, array $specs): string
    {
        $html = (string) ($product['description'] ?? '');
        $hi   = (string) ($product['highlights'] ?? '');
        $text = $this->plain($html);
        if ($text === '' || strcasecmp($text, $title) === 0) {
            $extra = $this->plain($hi);
            if ($extra !== '') {
                $text = $extra;
            }
        }
        if ($text === '' || strcasecmp($text, $title) === 0) {
            $bits = [];
            foreach ($specs as $k => $v) {
                if ($v !== '') {
                    $bits[] = $k . ': ' . $v;
                }
            }
            $text = $bits !== [] ? implode('. ', $bits) : $title . '. Shop this product on Solqam Marketplace.';
        }
        if (mb_strlen($text) > 5000) {
            $text = rtrim(mb_substr($text, 0, 4997)) . '...';
        }

        return $text;
    }

    protected function productType(CategoryModel $cats, int $categoryId, string $fallback): string
    {
        $crumbs = [];
        try {
            $crumbs = $cats->breadcrumb($categoryId);
        } catch (\Throwable $e) {
        }
        $names = [];
        foreach ($crumbs as $c) {
            $n = $this->plain((string) ($c['name'] ?? ''));
            if ($n !== '') {
                $names[] = $n;
            }
        }
        if (count($names) < 2 && $fallback !== '' && ! in_array($fallback, $names, true)) {
            $names[] = $this->plain($fallback);
        }
        if ($names === []) {
            return 'Marketplace > General';
        }
        if (count($names) === 1) {
            $names[] = 'General';
        }

        return implode(' > ', array_slice($names, 0, 4));
    }

    protected function rootLabel(string $path): string
    {
        $root = strtolower(trim(explode('>', $path)[0] ?? ''));
        $root = preg_replace('/[^a-z0-9]+/', '_', $root) ?? $root;

        return trim($root, '_') ?: 'general';
    }

    protected function brand(array $product): string
    {
        $brand = trim((string) ($product['brand'] ?? ''));

        return $brand !== '' ? $this->plain($brand) : 'Solqam';
    }

    protected function storeName(array $product): string
    {
        $store = trim((string) ($product['store_name'] ?? ''));
        if ($store !== '') {
            return $this->plain($store);
        }
        $seller = trim((string) ($product['seller_name'] ?? ''));

        return $seller !== '' ? $this->plain($seller) : 'Solqam';
    }

    protected function discountLabel(float $pct): string
    {
        if ($pct >= 30 && $pct <= 40.5) {
            return '30-40% off';
        }
        if ($pct >= 10 && $pct < 30) {
            return '10-30% off';
        }
        if ($pct > 40.5) {
            return '30-40% off';
        }

        return 'no discount';
    }

    protected function priceBand(float $price): string
    {
        if ($price < 1000) {
            return 'under 1000';
        }
        if ($price <= 3000) {
            return '1000-3000';
        }
        if ($price <= 5000) {
            return '3000-5000';
        }

        return '5000+';
    }

    protected function genderFrom(string $hay): string
    {
        $h = strtolower($hay);
        if (preg_match('/\b(kids?|baby|infant|toddler|children|boys?|girls?)\b/', $h)) {
            return 'unisex';
        }
        if (preg_match('/\b(women|woman|ladies|female|girl)\b/', $h)) {
            return 'female';
        }
        if (preg_match('/\b(men|man|male|gents)\b/', $h)) {
            return 'male';
        }

        return '';
    }

    protected function ageFrom(string $hay): string
    {
        $h = strtolower($hay);
        if (preg_match('/\b(kids?|baby|infant|toddler|children|boys?|girls?)\b/', $h)) {
            return 'kids';
        }
        if ($this->genderFrom($hay) !== '') {
            return 'adult';
        }

        return '';
    }

    protected function googleCategory(string $path): string
    {
        $h = strtolower($path);
        $map = [
            'phone'       => '267',
            'mobile'      => '267',
            'charger'     => '264',
            'electronic'  => '222',
            'laptop'      => '328',
            'kitchen'     => '638',
            'home'        => '536',
            'beauty'      => '469',
            'cloth'       => '1604',
            'women'       => '1604',
            'men'         => '1604',
            'shoe'        => '187',
            'bag'         => '6551',
            'watch'       => '201',
            'toy'         => '1239',
            'grocery'     => '412',
        ];
        foreach ($map as $needle => $id) {
            if (str_contains($h, $needle)) {
                return $id;
            }
        }

        return '';
    }

    protected function fbCategory(string $path): string
    {
        $h = strtolower($path);
        if (str_contains($h, 'electronic') || str_contains($h, 'mobile') || str_contains($h, 'phone')) {
            return 'electronics';
        }
        if (str_contains($h, 'home') || str_contains($h, 'kitchen')) {
            return 'home';
        }
        if (str_contains($h, 'cloth') || str_contains($h, 'women') || str_contains($h, 'men') || str_contains($h, 'fashion')) {
            return 'clothing_and_accessories';
        }
        if (str_contains($h, 'beauty')) {
            return 'health_and_beauty';
        }

        return '';
    }

    protected function parseSpecs(string $raw): array
    {
        $out = [];
        foreach (preg_split('/\r\n|\n|;/', $raw) ?: [] as $line) {
            if (! str_contains($line, ':')) {
                continue;
            }
            [$k, $v] = array_map('trim', explode(':', $line, 2));
            if ($k !== '' && $v !== '') {
                $out[$k] = $this->plain($v);
            }
        }

        return $out;
    }

    protected function lifestyleFirst(array $urls): array
    {
        $life = [];
        $rest = [];
        foreach ($urls as $url) {
            if (preg_match('/lifestyle|model|worn|on-body|onbody|use[-_]?in/i', $url)) {
                $life[] = $url;
            } else {
                $rest[] = $url;
            }
        }

        return array_merge($life, $rest);
    }

    protected function isNewArrival(string $created): bool
    {
        if ($created === '') {
            return false;
        }
        $ts = strtotime($created);

        return $ts !== false && $ts >= time() - 30 * 86400;
    }

    protected function money(float $n): string
    {
        return number_format($n, 2, '.', '') . ' PKR';
    }

    protected function isoRange(string $start, string $end): string
    {
        $tz = new DateTimeZone('Asia/Karachi');
        try {
            $a = (new DateTime($start, $tz))->format('Y-m-d\TH:i:sP');
            $b = (new DateTime($end, $tz))->format('Y-m-d\TH:i:sP');

            return $a . '/' . $b;
        } catch (\Throwable $e) {
            return '';
        }
    }

    protected function httpsUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (str_starts_with($url, '//')) {
            $url = 'https:' . $url;
        }
        if (str_starts_with($url, 'http://') && ! str_contains($url, 'localhost') && ! str_contains($url, '127.0.0.1')) {
            $url = 'https://' . substr($url, 7);
        }

        return $url;
    }

    protected function plain(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return trim($text);
    }

    protected function titleCase(string $s): string
    {
        return mb_convert_case(mb_strtolower($s), MB_CASE_TITLE, 'UTF-8');
    }
}
