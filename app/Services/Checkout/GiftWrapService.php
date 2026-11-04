<?php

namespace App\Services\Checkout;

use App\Models\CartItemModel;
use App\Services\Platform\SettingService;

class GiftWrapService
{
    public const DEFAULT_FEE = 150.0;

    protected static ?bool $ready = null;
    protected static ?bool $enabled = null;
    protected static ?float $fee = null;

    public static function enabled(): bool
    {
        if (self::$enabled !== null) {
            return self::$enabled;
        }
        if (! self::cartReady()) {
            return self::$enabled = false;
        }
        $v = strtolower(trim(SettingService::get('gift_wrap_enabled', '1')));

        return self::$enabled = ($v !== '0' && $v !== 'off' && $v !== 'false');
    }

    public static function cartReady(): bool
    {
        if (self::$ready !== null) {
            return self::$ready;
        }
        $marker = \App\Services\Platform\SchemaHeal::giftWrapMarkerPath();
        if (is_file($marker)) {
            return self::$ready = true;
        }
        try {
            $db = \Config\Database::connect();
            self::$ready = $db->tableExists('cart_items') && $db->fieldExists('gift_wrap', 'cart_items');
        } catch (\Throwable $e) {
            self::$ready = false;
        }

        return self::$ready;
    }

    public static function unitFee(): float
    {
        if (self::$fee !== null) {
            return self::$fee;
        }
        $raw = SettingService::get('gift_wrap_fee', (string) self::DEFAULT_FEE);
        $fee = (float) $raw;

        return self::$fee = max(0.0, round($fee, 2));
    }

    public static function isWrapped(array $row): bool
    {
        return (int) ($row['gift_wrap'] ?? 0) === 1;
    }

    public static function lineFee(int $qty, bool $wrapped): float
    {
        if (! $wrapped || ! self::enabled()) {
            return 0.0;
        }

        return round(self::unitFee() * max(1, $qty), 2);
    }

    public static function feeFromRow(array $row): float
    {
        if (isset($row['gift_wrap_fee']) && $row['gift_wrap_fee'] !== null && $row['gift_wrap_fee'] !== '') {
            return max(0.0, round((float) $row['gift_wrap_fee'], 2));
        }

        return self::lineFee((int) ($row['quantity'] ?? 1), self::isWrapped($row));
    }

    public static function sanitizeMessage(?string $msg): ?string
    {
        $t = trim((string) $msg);
        $t = preg_replace('/\s+/u', ' ', $t) ?? $t;
        if ($t === '') {
            return null;
        }
        if (function_exists('mb_substr')) {
            $t = mb_substr($t, 0, 400);
        } else {
            $t = substr($t, 0, 400);
        }

        return $t;
    }

    public static function enrichItems(array $items): array
    {
        foreach ($items as &$item) {
            $qty = max(1, (int) ($item['quantity'] ?? 1));
            $unit = (float) ($item['unit_price'] ?? $item['price'] ?? 0);
            $wrapped = self::isWrapped($item);
            $item['gift_wrap'] = $wrapped ? 1 : 0;
            $item['gift_hide_price'] = ! empty($item['gift_hide_price']) ? 1 : 0;
            $item['gift_wrap_fee'] = self::lineFee($qty, $wrapped);
            $item['line_goods'] = round($unit * (int) ($item['quantity'] ?? 1), 2);
            $item['line_total'] = round($item['line_goods'] + $item['gift_wrap_fee'], 2);
        }
        unset($item);

        return $items;
    }

    public static function wrapTotal(array $items): float
    {
        $total = 0.0;
        foreach ($items as $item) {
            $total += self::feeFromRow($item);
        }

        return round($total, 2);
    }

    public static function goodsTotal(array $items): float
    {
        $total = 0.0;
        foreach ($items as $item) {
            $unit = (float) ($item['unit_price'] ?? $item['price'] ?? 0);
            $total += $unit * (int) ($item['quantity'] ?? 1);
        }

        return round($total, 2);
    }

    /**
     * @param array<string, mixed> $post
     */
    public static function applyCartPost(int $cartId, array $post): void
    {
        if ($cartId < 1 || ! self::enabled() || ! self::cartReady()) {
            return;
        }
        $model = new CartItemModel();
        $itemId = (int) ($post['item_id'] ?? 0);
        if ($itemId > 0) {
            $row = $model->where('id', $itemId)->where('cart_id', $cartId)->first();
            if (! $row) {
                return;
            }
            $wrapped = ! empty($post['gift_wrap']);
            $model->update($itemId, [
                'gift_wrap'       => $wrapped ? 1 : 0,
                'gift_message'    => $wrapped ? self::sanitizeMessage($post['gift_message'] ?? null) : null,
                'gift_hide_price' => ($wrapped && ! empty($post['gift_hide_price'])) ? 1 : 0,
            ]);

            return;
        }

        if (empty($post['gift_sync'])) {
            return;
        }
        $giftItems = is_array($post['gift_item'] ?? null) ? $post['gift_item'] : [];
        $messages  = is_array($post['gift_message'] ?? null) ? $post['gift_message'] : [];
        $hides     = is_array($post['gift_hide'] ?? null) ? $post['gift_hide'] : [];
        $rows = $model->where('cart_id', $cartId)->findAll();
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $wrapped = ! empty($giftItems[$id]) || ! empty($giftItems[(string) $id]);
            $msg = is_array($messages) ? ($messages[$id] ?? $messages[(string) $id] ?? null) : null;
            $hide = is_array($hides) && (! empty($hides[$id]) || ! empty($hides[(string) $id]));
            $model->update($id, [
                'gift_wrap'       => $wrapped ? 1 : 0,
                'gift_message'    => $wrapped ? self::sanitizeMessage(is_string($msg) ? $msg : null) : null,
                'gift_hide_price' => ($wrapped && $hide) ? 1 : 0,
            ]);
        }
    }
}
