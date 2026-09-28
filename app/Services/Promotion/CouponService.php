<?php

namespace App\Services\Promotion;

use App\Models\CouponModel;
use App\Models\CouponRedemptionModel;
use RuntimeException;

class CouponService
{
    protected CouponModel $couponModel;
    protected CouponRedemptionModel $redemptionModel;

    public function __construct()
    {
        $this->couponModel     = new CouponModel();
        $this->redemptionModel = new CouponRedemptionModel();
    }

    public function apply(string $code, int $userId, float $subtotal): array
    {
        $code = strtoupper(trim($code));
        if ($code === '') {
            throw new RuntimeException('Enter a voucher code.');
        }

        $coupon = $this->couponModel->where('code', $code)->first();
        if (!$coupon || !(int) $coupon['is_active']) {
            throw new RuntimeException('Voucher code is invalid.');
        }

        $now = time();
        if (!empty($coupon['starts_at']) && strtotime($coupon['starts_at']) > $now) {
            throw new RuntimeException('This voucher is not active yet.');
        }
        if (!empty($coupon['expires_at']) && strtotime($coupon['expires_at']) < $now) {
            throw new RuntimeException('This voucher has expired.');
        }
        if ((float) $subtotal < (float) $coupon['min_order']) {
            throw new RuntimeException('Minimum order for this voucher is Rs. ' . number_format((float) $coupon['min_order'], 0));
        }
        if ($coupon['max_uses'] !== null && (int) $coupon['used_count'] >= (int) $coupon['max_uses']) {
            throw new RuntimeException('This voucher has reached its usage limit.');
        }

        $already = $this->redemptionModel
            ->where('coupon_id', $coupon['id'])
            ->where('user_id', $userId)
            ->first();
        if ($already) {
            throw new RuntimeException('You have already used this voucher.');
        }

        $discount = $coupon['type'] === 'percent'
            ? round($subtotal * ((float) $coupon['value'] / 100), 2)
            : (float) $coupon['value'];

        $discount = min($discount, $subtotal);

        return [
            'coupon'   => $coupon,
            'discount' => $discount,
        ];
    }

    public function redeem(int $couponId, int $userId, int $orderId): void
    {
        $this->redemptionModel->insert([
            'coupon_id' => $couponId,
            'user_id'   => $userId,
            'order_id'  => $orderId,
        ]);
        $this->couponModel->set('used_count', 'used_count + 1', false)->where('id', $couponId)->update();
    }
}
