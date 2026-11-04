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
        $ten   = $this->lastTen($phone);
        $email = strtolower(trim($email));

        $existing = $this->findCustomerByMobile($ten);
        if ($existing) {
            $this->reviveCustomer($existing, $name);

            return (int) $existing['id'];
        }

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && ! self::isGuestEmail($email)) {
            $byEmail = $this->usersTable()->where('email', $email)->get()->getRowArray();
            if ($byEmail && (($byEmail['role'] ?? '') === 'customer' || ($byEmail['role'] ?? '') === '')) {
                $this->reviveCustomer($byEmail, $name);

                return (int) $byEmail['id'];
            }
        }

        $storePhone = $this->phoneTakenByOtherRole($ten) ? ('oauth:g' . $ten) : $phone;
        $id = $this->insertGuest($name, $storePhone, $email);
        if ($id < 1) {
            $again = $this->findCustomerByMobile($ten);
            if ($again) {
                $this->reviveCustomer($again, $name);

                return (int) $again['id'];
            }
            throw new \RuntimeException('Could not start guest checkout. Please try again or sign up.');
        }

        try {
            (new WalletService())->getOrCreateWallet($id);
        } catch (\Throwable $e) {
        }

        return $id;
    }

    public function attachSessionCart(int $userId): void
    {
        $sid = session_id();
        if ($sid === '' || $userId < 1) {
            return;
        }
        $carts = new CartModel();
        $guest = $carts->where('session_id', $sid)->groupStart()
            ->where('user_id', null)
            ->orWhere('user_id', 0)
            ->groupEnd()
            ->first();
        if (! $guest) {
            $guest = $carts->where('session_id', $sid)->first();
        }
        if (! $guest) {
            return;
        }
        $owned = $carts->where('user_id', $userId)->first();
        if ($owned && (int) $owned['id'] !== (int) $guest['id']) {
            $this->mergeCartItems((int) $guest['id'], (int) $owned['id']);
            $carts->delete((int) $guest['id']);
        } else {
            $carts->update((int) $guest['id'], ['user_id' => $userId]);
        }
    }

    /**
     * @return list<string>
     */
    protected function phoneKeys(string $ten): array
    {
        return [
            '0' . $ten,
            $ten,
            '92' . $ten,
            '+92' . $ten,
            'oauth:g' . $ten,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function findCustomerByMobile(string $ten): ?array
    {
        if (strlen($ten) < 10) {
            return null;
        }
        $rows = $this->usersTable()
            ->groupStart()
            ->whereIn('phone', $this->phoneKeys($ten))
            ->orLike('phone', $ten)
            ->groupEnd()
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $role = (string) ($row['role'] ?? 'customer');
            if ($role !== 'customer' && $role !== '') {
                continue;
            }
            $stored = (string) ($row['phone'] ?? '');
            if ($this->lastTen($stored) === $ten || in_array($stored, $this->phoneKeys($ten), true)) {
                return $row;
            }
        }

        return null;
    }

    protected function phoneTakenByOtherRole(string $ten): bool
    {
        if (strlen($ten) < 10) {
            return false;
        }
        $row = $this->usersTable()
            ->whereIn('phone', $this->phoneKeys($ten))
            ->whereIn('role', ['seller', 'admin'])
            ->get()
            ->getRowArray();

        return (bool) $row;
    }

    /**
     * @param array<string, mixed> $user
     */
    protected function reviveCustomer(array $user, string $name): void
    {
        $id    = (int) ($user['id'] ?? 0);
        $patch = [];
        if (! empty($user['deleted_at'])) {
            $patch['deleted_at'] = null;
        }
        if (($user['status'] ?? '') === 'rejected' || ($user['status'] ?? '') === '') {
            $patch['status'] = 'active';
        }
        if ($name !== '' && trim((string) ($user['name'] ?? '')) === '') {
            $patch['name'] = $name;
        }
        if ($id > 0 && $patch !== []) {
            try {
                $this->usersTable()->where('id', $id)->update($patch);
            } catch (\Throwable $e) {
            }
        }
    }

    protected function insertGuest(string $name, string $phone, string $email): int
    {
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || self::isGuestEmail($email)) {
            $email = 'guest.' . bin2hex(random_bytes(6)) . '@' . self::EMAIL_DOMAIN;
        }

        $users = new UserModel();
        $users->skipValidation(true);
        try {
            $id = $users->insert([
                'name'          => $name !== '' ? $name : 'Guest customer',
                'email'         => $email,
                'phone'         => $phone,
                'password_hash' => password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT),
                'role'          => 'customer',
                'status'        => 'active',
                'is_verified'   => 0,
            ], true);
            if ($id) {
                return (int) $id;
            }
        } catch (\Throwable $e) {
            log_message('error', 'Guest user insert: ' . $e->getMessage());
        }

        $again = $this->findCustomerByMobile($this->lastTen($phone));
        if ($again) {
            return (int) $again['id'];
        }

        return 0;
    }

    protected function usersTable()
    {
        return \Config\Database::connect()->table('users');
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
                $upd = ['quantity' => (int) $exist['quantity'] + (int) $row['quantity']];
                if (! empty($row['gift_wrap'])) {
                    $upd['gift_wrap'] = 1;
                    if (! empty($row['gift_message'])) {
                        $upd['gift_message'] = $row['gift_message'];
                    }
                    if (! empty($row['gift_hide_price'])) {
                        $upd['gift_hide_price'] = 1;
                    }
                }
                $db->table('cart_items')->where('id', $exist['id'])->update($upd);
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
        $ten = $this->lastTen($phone);

        return strlen($ten) === 10 ? ('0' . $ten) : trim($phone);
    }

    protected function lastTen(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (strlen($digits) > 10) {
            $digits = substr($digits, -10);
        }

        return $digits;
    }
}
