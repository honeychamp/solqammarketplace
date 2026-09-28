# Solqam Market Place

**A Pakistan B2C Multi-Vendor Marketplace MVP** built with CodeIgniter 4, MySQL, and Bootstrap 5.

> Month 1 MVP · API-first · Customer / Seller / Admin Portals · Append-only Wallet Ledger · COD + JazzCash Sandbox

---

## Features

| Feature | Detail |
|---------|--------|
| **Roles** | Customer, Seller, Admin — session-based auth + API bearer token |
| **Seller Onboarding** | Registration → Mock OTP → Admin approval → Portal access |
| **Product Catalog** | Categories, search/filter, image upload, stock tracking |
| **Cart & Checkout** | Multi-vendor cart, COD & JazzCash Sandbox, wallet deduction |
| **Wallet Ledger** | 100% append-only ledger; balance = `SUM(credits) - SUM(debits)` |
| **10% Cashback** | Auto-credited on `delivered` order status via ledger entry |
| **Commission** | Single global % rate (default 10 %); configurable by Admin |
| **Returns & Refunds** | Customer raise → Admin approve/reject → wallet credit on approval |
| **Reviews** | One verified review per delivered order item |
| **API v1** | `/api/v1/...` RESTful endpoints for future mobile apps |
| **Admin Dashboard** | Sales charts (Chart.js), seller approvals, reports |

---

## Default Credentials (Seeded)

| Role | Email | Password |
|------|-------|----------|
| Admin | `admin@solqam.pk` | `admin123` |
| Seller 1 | `seller1@solqam.pk` | `seller123` |
| Seller 2 | `seller2@solqam.pk` | `seller123` |
| Seller 3 | `seller3@solqam.pk` | `seller123` |
| Pending Seller | `pending@solqam.pk` | `seller123` |
| Customer 1 | `customer1@solqam.pk` | `customer123` |
| Customer 2 | `customer2@solqam.pk` | `customer123` |
| Customer 3 | `customer3@solqam.pk` | `customer123` |

---

## Requirements

- PHP 8.2+ with extensions: `mysqli`, `intl`, `mbstring`, `zip`, `json`
- MySQL 8.0+
- Composer 2.x
- (Optional) Docker & Docker Compose

---

## Quick Start (XAMPP / Local)

```bash
# 1. Clone or unzip the project
cd c:/xampp/htdocs/solqamtech

# 2. Install dependencies
composer install

# 3. Copy env and configure database
cp env .env
# Edit .env → set database.default.hostname, .database, .username, .password

# 4. Run migrations
php spark migrate

# 5. Seed demo data
php spark db:seed MarketplaceSeeder

# 6. Start local server (or use XAMPP Apache with http://localhost/solqamtech/public)
php spark serve
```

Then open: **http://localhost:8080**

---

## Docker Setup

```bash
# Build and start containers
docker compose up --build -d

# Run migrations inside the container
docker compose exec app php spark migrate

# Seed demo data
docker compose exec app php spark db:seed MarketplaceSeeder
```

| Service | URL |
|---------|-----|
| App | http://localhost:8080 |
| phpMyAdmin | http://localhost:8081 |

---

## Project Structure

```
app/
├── Config/          # Routes.php, Filters.php
├── Controllers/
│   ├── Api/V1/      # REST endpoints (AuthController, ProductsController, …)
│   ├── Auth/        # Web login, register, OTP verify
│   ├── Customer/    # Home, Shop, Cart, Checkout, Orders, Wallet
│   ├── Seller/      # Dashboard, Product CRUD, Order management
│   └── Admin/       # Dashboard, Sellers, Categories, Products, Orders, Reports
├── Database/
│   ├── Migrations/  # Single migration — all 18 tables
│   └── Seeds/       # MarketplaceSeeder.php
├── Filters/         # AuthFilter, RoleFilter, SellerApprovalFilter, ApiAuthFilter
├── Models/          # 17 models
├── Services/
│   ├── Auth/        # AuthService (session + API token)
│   ├── Commission/  # CommissionService
│   ├── Order/       # OrderService (atomic checkout + cashback)
│   ├── Otp/         # OtpServiceInterface + MockOtpService
│   ├── Payment/     # PaymentGatewayInterface + JazzCashSandboxAdapter
│   └── Wallet/      # WalletService (append-only ledger)
└── Views/
    ├── layouts/     # main.php, seller.php, admin.php
    ├── auth/        # login, register, verify_otp
    ├── customer/    # home, catalog, product_detail, cart, checkout, orders, wallet
    ├── seller/      # dashboard, products/, orders/
    └── admin/       # dashboard, sellers/, categories/, products/, orders/, …
```

---

## API v1 Endpoints

All API responses are JSON. Auth-required endpoints need `Authorization: Bearer <token>` header.

| Method | Endpoint | Auth |
|--------|----------|------|
| POST | `/api/v1/auth/register` | Public |
| POST | `/api/v1/auth/verify-otp` | Public |
| POST | `/api/v1/auth/login` | Public |
| GET | `/api/v1/products` | Public |
| GET | `/api/v1/products/{id}` | Public |
| GET | `/api/v1/categories` | Public |
| GET | `/api/v1/cart` | Public |
| POST | `/api/v1/cart/add` | Public |
| POST | `/api/v1/cart/update` | Public |
| POST | `/api/v1/cart/remove` | Public |
| GET | `/api/v1/orders` | Bearer |
| GET | `/api/v1/orders/{id}` | Bearer |
| POST | `/api/v1/orders/checkout` | Bearer |
| GET | `/api/v1/wallet/balance` | Bearer |
| GET | `/api/v1/wallet/transactions` | Bearer |

---

## Payment (COD now, PayFast later)

Checkout currently offers **Cash on Delivery** only.  
When `payfast.merchantId` and `payfast.securedKey` are set, checkout shows **Pay online (PayFast)**. JazzCash, EasyPaisa and cards are paid on PayFast’s page. The order stays **pending** until PayFast success/IPN (`err_code` 00).

Portal URLs:

- Success: `{baseURL}payments/payfast/success`
- Failure: `{baseURL}payments/payfast/failure`
- IPN: `{baseURL}payments/payfast/ipn`

---

## SMS / OTP

Set `sms.driver` to `jazzcmt`, `twilio`, or `http`, then `sms.demoBypass = false`.  
OTP is 6 digits, 10 minutes. Demo mode (`sms.driver = mock`) can still use `1234`.

---

## Wallet Ledger Guarantee

> **Rule**: No balance column is ever updated. Balance is always computed from the ledger.

```sql
-- Compute wallet balance for user #5
SELECT
  SUM(CASE WHEN type = 'credit' THEN amount ELSE -amount END) AS balance
FROM wallet_transactions
WHERE wallet_id = (SELECT id FROM wallets WHERE user_id = 5);
```

The `WalletService::debit()` method checks computed balance **before** inserting any row and throws a `RuntimeException` if funds are insufficient.

---

## Running Tests

```bash
# All feature tests
vendor/bin/phpunit tests/feature/

# Individual suites
vendor/bin/phpunit tests/feature/WalletLedgerTest.php
vendor/bin/phpunit tests/feature/OrderStatusTransitionTest.php
vendor/bin/phpunit tests/feature/CheckoutFlowTest.php
```

> Tests use the live MySQL database (`solqamtech`). Each test creates isolated records and cleans them up in `tearDown()`.

---

## Commission Flow

```
Order Subtotal (PKR)
      │
      ├── Commission %  ──→  Platform revenue (stored on order)
      └── Net Seller Amount = Subtotal − Commission
```

- Admin sets the global rate at `/admin/commissions`.
- The rate is read at checkout time from the `commissions` table.
- Per-order commission is stored on `orders.commission_amount` for audit.

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Language | PHP 8.2 |
| Framework | CodeIgniter 4.7 |
| Database | MySQL 8.0 |
| Frontend | Bootstrap 5 · Chart.js · Vanilla JS |
| Auth | Custom session + bearer token (no Shield) |
| Payment | JazzCash Sandbox (interface-based) |
| Testing | PHPUnit via CI4 test helpers |
| DevOps | Docker Compose + XAMPP-friendly |

---

*Built for the Pakistani eCommerce market — prices in PKR, Pakistani city/province support, COD-first checkout.*
