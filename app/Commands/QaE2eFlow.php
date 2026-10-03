<?php

namespace App\Commands;

use App\Models\AddressModel;
use App\Models\CartItemModel;
use App\Models\CartModel;
use App\Models\CategoryModel;
use App\Models\ProductModel;
use App\Models\SellerProfileModel;
use App\Models\ShippingZoneModel;
use App\Models\UserModel;
use App\Services\Order\OrderService;
use App\Services\Platform\SchemaHeal;
use App\Services\Wallet\WalletService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;
use Throwable;

/**
 * Full marketplace smoke: catalog, seller, customer, COD, ship, deliver, wallets.
 */
class QaE2eFlow extends BaseCommand
{
    protected $group       = 'Solqam';
    protected $name        = 'qa:e2e';
    protected $description = 'Seed QA data and run start-to-end order flow (COD, fulfill, wallet, commission).';

    public function run(array $params)
    {
        $issues = [];
        $ok     = [];

        try {
            \Config\Database::seeder()->call('DatabaseSeeder');
            SchemaHeal::run();
            $ok[] = 'Admin seeder + SchemaHeal';
        } catch (Throwable $e) {
            $issues[] = 'Seed/heal: ' . $e->getMessage();
        }

        $db = Database::connect();
        $users = new UserModel();
        $admin = $users->where('role', 'admin')->orderBy('id', 'ASC')->first();
        if (! $admin) {
            CLI::error('No admin user after seed.');

            return EXIT_ERROR;
        }
        $adminId = (int) $admin['id'];
        $mallProfile = (new SellerProfileModel())->where('user_id', $adminId)->first();
        if (! $mallProfile) {
            (new SellerProfileModel())->insert([
                'user_id'         => $adminId,
                'store_name'      => 'Solqam Mall',
                'approval_status' => 'approved',
                'city'            => 'Lahore',
            ]);
        } else {
            (new SellerProfileModel())->update($mallProfile['id'], ['approval_status' => 'approved']);
        }

        $zones = new ShippingZoneModel();
        if (! $zones->where('city', 'Lahore')->first()) {
            $zones->insert([
                'city'       => 'Lahore',
                'province'   => 'Punjab',
                'rate'       => 150,
                'free_above' => 0,
                'eta_days'   => '2-4',
            ]);
        }
        $ok[] = 'Shipping zone Lahore Rs. 150';

        $cats = new CategoryModel();
        $cat  = $cats->where('slug', 'electronics')->first();
        if (! $cat) {
            $cid = $cats->insert([
                'name'                => 'Electronics',
                'slug'                => 'electronics',
                'description'         => 'Phones, audio, gadgets',
                'commission_percent'  => 12.00,
                'is_active'           => 1,
                'icon'                => 'bi-phone',
            ]);
            $cat = $cats->find($cid);
        } else {
            $cats->update($cat['id'], ['commission_percent' => 12.00, 'is_active' => 1]);
            $cat = $cats->find($cat['id']);
        }
        $ok[] = 'Category Electronics commission 12%';

        $sellerPwd = 'SolqamQa1';
        $buyerPwd  = 'SolqamQa1';
        $seller    = $this->ensureUser($users, [
            'name'     => 'QA Seller Ahmed',
            'email'    => 'qa.seller@solqam.test',
            'phone'    => '03001112221',
            'password' => $sellerPwd,
            'role'     => 'seller',
        ]);
        $buyer = $this->ensureUser($users, [
            'name'     => 'QA Buyer Sara',
            'email'    => 'qa.buyer@solqam.test',
            'phone'    => '03001112222',
            'password' => $buyerPwd,
            'role'     => 'customer',
        ]);

        $profiles = new SellerProfileModel();
        $sp       = $profiles->where('user_id', (int) $seller['id'])->first();
        if (! $sp) {
            $profiles->insert([
                'user_id'         => (int) $seller['id'],
                'store_name'      => 'QA Mart',
                'city'            => 'Lahore',
                'approval_status' => 'approved',
            ]);
        } else {
            $profiles->update($sp['id'], ['approval_status' => 'approved', 'store_name' => 'QA Mart', 'city' => 'Lahore']);
        }
        SchemaHeal::run();
        $ok[] = 'Seller QA Mart approved; customer verified';

        $products = new ProductModel();
        $mall     = $products->where('slug', 'qa-mall-earbuds')->first();
        if (! $mall) {
            $mallId = $products->insert([
                'seller_id'        => $adminId,
                'category_id'      => (int) $cat['id'],
                'name'             => 'QA Mall Earbuds',
                'slug'             => 'qa-mall-earbuds',
                'description'      => 'Admin store earbuds for QA.',
                'price'            => 2500,
                'stock'            => 20,
                'brand'            => 'Solqam Mall',
                'cashback_percent' => 5,
                'is_mall'          => 1,
                'status'           => 'active',
                'return_days'      => 7,
            ]);
            $mall = $products->find($mallId);
        }
        $sellerSku = $products->where('slug', 'qa-seller-cable')->first();
        if (! $sellerSku) {
            $sid = $products->insert([
                'seller_id'        => (int) $seller['id'],
                'category_id'      => (int) $cat['id'],
                'name'             => 'QA Seller Cable',
                'slug'             => 'qa-seller-cable',
                'description'      => 'Seller cable for QA checkout.',
                'price'            => 800,
                'stock'            => 30,
                'brand'            => 'QA Mart',
                'cashback_percent' => 3,
                'is_mall'          => 0,
                'status'           => 'active',
                'return_days'      => 7,
            ]);
            $sellerSku = $products->find($sid);
        }
        $ok[] = 'Products: mall earbuds + seller cable';

        $addrModel = new AddressModel();
        $addr      = $addrModel->where('user_id', (int) $buyer['id'])->where('city', 'Lahore')->first();
        if (! $addr) {
            $aid = $addrModel->insert([
                'user_id'         => (int) $buyer['id'],
                'recipient_name'  => 'QA Buyer Sara',
                'phone'           => '03001112222',
                'street_address'  => '12 QA Street Gulberg',
                'city'            => 'Lahore',
                'province'        => 'Punjab',
                'postal_code'     => '54000',
                'is_default'      => 1,
            ]);
            $addr = $addrModel->find($aid);
        }

        $wallet = new WalletService();
        $orders = new OrderService();

        $runCheckout = function (int $productId, string $label) use ($buyer, $addr, $orders, &$ok, &$issues) {
            $carts = new CartModel();
            $items = new CartItemModel();
            $prods = new ProductModel();
            $p     = $prods->find($productId);
            $cart  = $carts->getOrCreateCart((int) $buyer['id'], 'qa-e2e');
            $items->where('cart_id', (int) $cart['id'])->delete();
            $items->insert([
                'cart_id'     => (int) $cart['id'],
                'product_id'  => $productId,
                'quantity'    => 1,
                'unit_price'  => (float) $p['price'],
            ]);
            try {
                $result = $orders->checkout((int) $buyer['id'], (int) $addr['id'], 'cod', false, 'QA ' . $label);
                $ok[]   = $label . ' COD ' . ($result['order_number'] ?? '') . ' payable ' . ($result['final_payable'] ?? '?');

                return $result;
            } catch (Throwable $e) {
                $issues[] = $label . ' checkout: ' . $e->getMessage();

                return null;
            }
        };

        $sellerOrder = $runCheckout((int) $sellerSku['id'], 'Seller product');
        if ($sellerOrder) {
            try {
                $oid = (int) $sellerOrder['order_id'];
                $orders->updateSellerShipment($oid, (int) $seller['id'], 'confirmed');
                $orders->updateSellerShipment($oid, (int) $seller['id'], 'shipped', [
                    'courier'         => 'TCS',
                    'tracking_number' => 'QA-TCS-001',
                ]);
                $orders->updateSellerShipment($oid, (int) $seller['id'], 'delivered');
                $ok[] = 'Seller self-ship confirmed → shipped → delivered';
            } catch (Throwable $e) {
                $issues[] = 'Seller fulfill: ' . $e->getMessage();
            }
        }

        $mallOrder = $runCheckout((int) $mall['id'], 'Admin mall product');
        if ($mallOrder) {
            try {
                $oid = (int) $mallOrder['order_id'];
                $orders->updateSellerShipment($oid, $adminId, 'confirmed');
                $orders->updateSellerShipment($oid, $adminId, 'shipped', [
                    'courier'         => 'Leopards',
                    'tracking_number' => 'QA-LHR-002',
                ]);
                $orders->updateSellerShipment($oid, $adminId, 'delivered');
                $ok[] = 'Admin mall confirmed → shipped → delivered';
            } catch (Throwable $e) {
                $issues[] = 'Admin mall fulfill: ' . $e->getMessage();
            }
        }

        $inboundOrder = $runCheckout((int) $sellerSku['id'], 'Inbound to Solqam');
        if ($inboundOrder) {
            try {
                $oid = (int) $inboundOrder['order_id'];
                $orders->setFulfillBy($oid, (int) $seller['id'], 'admin');
                $pkg = $db->table('shipments')->where('order_id', $oid)->where('seller_id', (int) $seller['id'])->get()->getRowArray();
                if ($pkg) {
                    $orders->receiveAdminInbound((int) $pkg['id']);
                    $orders->updateSellerShipment($oid, (int) $seller['id'], 'shipped', [
                        'courier'         => 'Solqam',
                        'tracking_number' => 'QA-INB-003',
                    ], true);
                    $orders->updateSellerShipment($oid, (int) $seller['id'], 'delivered', [], true);
                    $ok[] = 'Inbound receive → Solqam ship → delivered';
                } else {
                    $issues[] = 'Inbound shipment row missing';
                }
            } catch (Throwable $e) {
                $issues[] = 'Inbound flow: ' . $e->getMessage();
            }
        }

        $buyerBal  = $wallet->getBalance((int) $buyer['id']);
        $sellerBal = $wallet->getBalance((int) $seller['id']);
        $adminBal  = $wallet->getBalance($adminId);
        $ok[]      = sprintf('Wallets buyer=%.2f seller=%.2f admin=%.2f', $buyerBal, $sellerBal, $adminBal);

        if ($buyerBal > 0) {
            $carts = new CartModel();
            $items = new CartItemModel();
            $cart  = $carts->getOrCreateCart((int) $buyer['id'], 'qa-e2e');
            $items->where('cart_id', (int) $cart['id'])->delete();
            $items->insert([
                'cart_id'    => (int) $cart['id'],
                'product_id' => (int) $sellerSku['id'],
                'quantity'   => 1,
                'unit_price' => (float) $sellerSku['price'],
            ]);
            try {
                $pl = $orders->checkout(
                    (int) $buyer['id'],
                    (int) $addr['id'],
                    'pay_later',
                    true,
                    'QA pay later',
                    null,
                    [
                        'full_name'         => 'QA Buyer Sara',
                        'cnic_number'       => '35202-1234567-1',
                        'phone'             => '03001112222',
                        'address_text'      => '12 QA Street Gulberg',
                        'cnic_front_path'   => 'uploads/paylater/qa-front.jpg',
                        'cnic_back_path'    => 'uploads/paylater/qa-back.jpg',
                        'utility_bill_path' => 'uploads/paylater/qa-bill.jpg',
                    ]
                );
                $after = $wallet->getBalance((int) $buyer['id']);
                $ok[]  = 'Pay later ' . ($pl['order_number'] ?? '') . ' wallet after=' . $after;
                if ($after >= 0 && (float) ($pl['final_payable'] ?? 0) > 0) {
                    $issues[] = 'Pay later remaining did not go wallet minus (balance ' . $after . ')';
                }
            } catch (Throwable $e) {
                $issues[] = 'Pay later: ' . $e->getMessage();
            }
        } else {
            $issues[] = 'Buyer wallet 0 after deliveries — cashback may not have credited (check payment/delivery).';
        }

        $counts = [
            'users'      => $db->table('users')->countAllResults(),
            'products'   => $db->table('products')->countAllResults(),
            'orders'     => $db->table('orders')->countAllResults(),
            'payments'   => $db->tableExists('payments') ? $db->table('payments')->countAllResults() : 0,
            'shipments'  => $db->tableExists('shipments') ? $db->table('shipments')->countAllResults() : 0,
        ];
        $ok[] = 'DB counts ' . json_encode($counts);

        foreach ($ok as $line) {
            CLI::write('[ok] ' . $line, 'green');
        }
        foreach ($issues as $line) {
            CLI::write('[issue] ' . $line, 'red');
        }

        return $issues === [] ? EXIT_SUCCESS : EXIT_ERROR;
    }

    protected function ensureUser(UserModel $users, array $data): array
    {
        $existing = $users->where('email', $data['email'])->first();
        if ($existing) {
            $users->update($existing['id'], [
                'is_verified'   => 1,
                'status'        => 'active',
                'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
            ]);

            return $users->find($existing['id']);
        }

        $id = $users->insert([
            'name'          => $data['name'],
            'email'         => $data['email'],
            'phone'         => $data['phone'],
            'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
            'role'          => $data['role'],
            'status'        => 'active',
            'is_verified'   => 1,
            'api_token'     => bin2hex(random_bytes(16)),
        ]);
        (new WalletService())->getOrCreateWallet((int) $id);

        return $users->find($id);
    }
}
