<?php

namespace App\Services\Checkout;

use App\Models\CartModel;
use App\Models\UserModel;
use App\Services\Wallet\WalletService;

class GuestCheckoutService
{
    public const EMAIL_DOMAIN = 'guest.solqam.local';

    public static function isGuestEmail(?string $email): bool
    {
        $email = strtolower(trim((string) $email));

        return $email !== '' && str_ends_with($email, '@' . self::EMAIL_DOMAIN);
    }

    public function resolveBuyer(string $name, string $phone, string $email = ''): int
    {
        $name  = trim($name);
        $phone = $this->normalizePhone($phone);
        $email = strtolower(trim($email));
        $users = new UserModel();
        $users->skipValidation();

        $existing = $users->findByEmailOrPhone($phone);
        if ($existing && ($existing['role'] ?? '') === 'customer') {
            $patch = [];
            if ($name !== '' && trim((string) ($existing['name'] ?? '')) === '') {
                $patch['name'] = $name;
            }
            if ($patch !== []) {
                $users->update((int) $existing['id'], $patch);
            }

            return (int) $existing['id'];
        }

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && ! self::isGuestEmail($email)) {
            $byEmail = $users->where('email', $email)->first();
            if ($byEmail && ($byEmail['role'] ?? '') === 'customer') {
                return (int) $byEmail['id'];
            }
        } else {
            $email = 'guest.' . bin2hex(random_bytes(6)) . '@' . self::EMAIL_DOMAIN;
        }

        $id = $users->insert([
            'name'          => $name !== '' ? $name : 'Guest customer',
            'email'         => $email,
            'phone'         => $phone,
            'password_hash' => password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT),
            'role'          => 'customer',
            'status'        => 'active',
            'is_verified'   => 0,
        ]);
        if (! $id) {
            throw new \RuntimeException('Could not start guest checkout. Please try again or sign up.');
        }
        (new WalletService())->getOrCreateWallet((int) $id);

        return (int) $id;
    }

    public function attachSessionCart(int $userId): void
    {
        $sid = session_id();
        if ($sid === '' || $userId < 1) {
            return;
        }
        $carts = new CartModel();
        $guest = $carts->where('session_id', $sid)->where('user_id', null)->first();
        if ($guest) {
            $owned = $carts->where('user_id', $userId)->first();
            if ($owned && (int) $owned['id'] !== (int) $guest['id']) {
                $this->mergeCartItems((int) $guest['id'], (int) $owned['id']);
                $carts->delete((int) $guest['id']);
            } else {
                $carts->update((int) $guest['id'], ['user_id' => $userId]);
            }
        }
    }

    protected function mergeCartItems(int $fromCartId, int $toCartId): void
    {
        $db = \Config\Database::connect();
        $from = $db->table('cart_items')->where('cart_id', $fromCartId)->get()->getResultArray();
        foreach ($from as $row) {
            $q = $db->table('cart_items')->where('cart_id', $toCartId)->where('product_id', $row['product_id']);
            if (! empty($row['variant_id'])) {
                $q->where('variant_id', $row['variant_id']);
            } else {
                $q->where('variant_id', null);
            }
            $exist = $q->get()->getRowArray();
            if ($exist) {
                $db->table('cart_items')->where('id', $exist['id'])->update([
                    'quantity' => (int) $exist['quantity'] + (int) $row['quantity'],
                ]);
            } else {
                unset($row['id']);
                $row['cart_id'] = $toCartId;
                $db->table('cart_items')->insert($row);
            }
        }
        $db->table('cart_items')->where('cart_id', $fromCartId)->delete();
    }

    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (strlen($digits) === 10) {
            return '0' . $digits;
        }
        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            return $digits;
        }
        if (strlen($digits) === 12 && str_starts_with($digits, '92')) {
            return '0' . substr($digits, 2);
        }
        if (strlen($digits) === 13 && str_starts_with($digits, '920')) {
            return substr($digits, 2);
        }

        return $phone;
    }
}
