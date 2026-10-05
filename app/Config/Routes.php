<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// --------------------------------------------------------------------
// Public Customer Web Routes
// --------------------------------------------------------------------
$routes->get('sitemap.xml', 'SeoController::sitemap');
$routes->get('compare', 'Customer\CatalogController::compare');
$routes->get('compare/toggle/(:num)', 'Customer\CatalogController::compareToggle/$1');
$routes->get('/', 'Customer\HomeController::index');
$routes->get('shop', 'Customer\CatalogController::index');
$routes->get('flash-deals', 'Customer\CatalogController::flashDeals');
$routes->get('categories', 'Customer\CatalogController::index');
$routes->get('track', 'Customer\TrackController::index');
$routes->post('track', 'Customer\TrackController::index');
$routes->get('store/(:num)', 'Customer\StoreController::show/$1');
$routes->post('product/(:num)/question', 'Customer\CatalogController::askQuestion/$1', ['filter' => ['auth', 'role:customer']]);
$routes->post('wishlist/toggle', 'Customer\AccountController::toggleWishlist', ['filter' => 'auth']);
$routes->post('store/follow', 'Customer\AccountController::toggleFollow', ['filter' => 'auth']);
$routes->get('help', 'Customer\HelpController::index');
$routes->get('commission', 'Customer\PagesController::commission');
$routes->get('seller-policies', 'Customer\PagesController::policies');
$routes->get('fulfillment', 'Customer\PagesController::fulfillment');
$routes->get('returns-policy', 'Customer\PagesController::returns');
$routes->get('shipping-info', 'Customer\PagesController::shipping');
$routes->get('product/(:num)', 'Customer\CatalogController::detail/$1');
$routes->get('cart', 'Customer\CartController::index');
$routes->post('cart/add', 'Customer\CartController::add');
$routes->post('cart/update', 'Customer\CartController::update');
$routes->get('cart/remove/(:num)', 'Customer\CartController::remove/$1');

// --------------------------------------------------------------------
// Authentication Web Routes
// --------------------------------------------------------------------
$routes->match(['GET', 'POST'], 'login', 'Auth\AuthController::login');
$routes->match(['GET', 'POST'], 'register', 'Auth\AuthController::register');
$routes->match(['GET', 'POST'], 'verify-otp', 'Auth\AuthController::verifyOtp');
$routes->post('verify-otp/resend', 'Auth\AuthController::resendSignupOtp');
$routes->match(['GET', 'POST'], 'forgot-password', 'Auth\AuthController::forgotPassword');
$routes->match(['GET', 'POST'], 'forgot-password/verify', 'Auth\AuthController::forgotVerify');
$routes->post('forgot-password/verify/resend', 'Auth\AuthController::resendResetOtp');
$routes->match(['GET', 'POST'], 'forgot-password/reset', 'Auth\AuthController::forgotReset');
$routes->get('logout', 'Auth\AuthController::logout');

// --------------------------------------------------------------------
// Customer Account & Checkout Routes (Auth & Role Guarded)
// --------------------------------------------------------------------
$routes->match(['GET', 'POST'], 'payments/payfast/success', 'Payments\CallbackController::payfastSuccess');
$routes->match(['GET', 'POST'], 'payments/payfast/failure', 'Payments\CallbackController::payfastFailure');
$routes->post('payments/payfast/ipn', 'Payments\CallbackController::payfastIpn');

$routes->group('', ['filter' => ['auth', 'role:customer']], static function ($routes) {
    $routes->get('checkout', 'Customer\CheckoutController::index');
    $routes->post('checkout', 'Customer\CheckoutController::process');
    $routes->post('checkout/coupon', 'Customer\CheckoutController::applyCoupon');
    $routes->get('checkout/shipping-quote', 'Customer\CheckoutController::shippingQuote');
    $routes->get('checkout/pay/(:num)', 'Customer\CheckoutController::pay/$1');

    $routes->group('account', static function ($routes) {
        $routes->get('orders', 'Customer\OrderController::index');
        $routes->get('orders/(:num)', 'Customer\OrderController::show/$1');
        $routes->get('orders/(:num)/invoice', 'Customer\OrderController::invoice/$1');
        $routes->post('orders/(:num)/cancel', 'Customer\OrderController::cancel/$1');
        $routes->post('orders/(:num)/return', 'Customer\OrderController::requestReturn/$1');
        $routes->post('orders/(:num)/review', 'Customer\OrderController::submitReview/$1');
        $routes->get('wallet', 'Customer\AccountController::wallet');
        $routes->get('wishlist', 'Customer\AccountController::wishlist');
        $routes->match(['GET', 'POST'], 'addresses', 'Customer\AccountController::addresses');
    });

    $routes->get('messages', 'Customer\ChatController::index');
    $routes->get('messages/start', 'Customer\ChatController::start');
    $routes->get('messages/(:num)', 'Customer\ChatController::show/$1');
    $routes->post('messages/(:num)/send', 'Customer\ChatController::send/$1');
    $routes->post('help/tickets', 'Customer\HelpController::store');
    $routes->get('help/tickets/(:num)', 'Customer\HelpController::show/$1');
    $routes->post('help/tickets/(:num)/reply', 'Customer\HelpController::reply/$1');
});

// --------------------------------------------------------------------
// Seller Portal Routes (Auth, Role Seller, & Approval Guarded)
// --------------------------------------------------------------------
$routes->group('seller', ['filter' => ['auth', 'role:seller', 'seller_approved']], static function ($routes) {
    $routes->get('dashboard', 'Seller\DashboardController::index');
    $routes->get('performance', 'Seller\DashboardController::performance');

    // Products CRUD
    $routes->get('products', 'Seller\ProductController::index');
    $routes->get('products/create', 'Seller\ProductController::create');
    $routes->post('products/store', 'Seller\ProductController::store');
    $routes->post('products/import', 'Seller\ProductController::importCsv');
    $routes->get('orders/(:num)/slip', 'Seller\OrderController::slip/$1');
    $routes->get('products/edit/(:num)', 'Seller\ProductController::edit/$1');
    $routes->post('products/update/(:num)', 'Seller\ProductController::update/$1');
    $routes->post('products/(:num)/stock', 'Seller\ProductController::quickStock/$1');
    $routes->get('products/delete/(:num)', 'Seller\ProductController::delete/$1');

    // Orders Management
    $routes->get('orders', 'Seller\OrderController::index');
    $routes->get('orders/(:num)', 'Seller\OrderController::show/$1');
    $routes->post('orders/(:num)/status', 'Seller\OrderController::updateStatus/$1');
    $routes->post('orders/(:num)/fulfill', 'Seller\OrderController::setFulfill/$1');
    $routes->get('customers', 'Seller\CustomerController::index');
    $routes->get('customers/(:num)', 'Seller\CustomerController::show/$1');
    $routes->get('questions', 'Seller\HubController::questions');
    $routes->post('questions/(:num)/answer', 'Seller\HubController::answer/$1');
    $routes->get('payouts', 'Seller\HubController::payouts');
    $routes->get('payouts/statement', 'Seller\HubController::payoutStatement');
    $routes->get('campaigns', 'Seller\CampaignController::index');
    $routes->post('campaigns/join', 'Seller\CampaignController::join');
    $routes->get('products/csv-template', 'Seller\ProductController::csvTemplate');
    $routes->get('messages', 'Seller\ChatController::index');
    $routes->get('messages/(:num)', 'Seller\ChatController::show/$1');
    $routes->post('messages/(:num)/send', 'Seller\ChatController::send/$1');
});

// --------------------------------------------------------------------
// Dedicated Admin Authentication Routes (Isolated from Customer & Seller)
// --------------------------------------------------------------------
$routes->get('admin', 'Admin\AuthController::entry');
$routes->match(['GET', 'POST'], 'admin/login', 'Admin\AuthController::login');
$routes->get('admin/logout', 'Admin\AuthController::logout');

// --------------------------------------------------------------------
// Admin Console Routes (Auth & Role Admin Guarded)
// --------------------------------------------------------------------
$routes->group('admin', ['filter' => ['auth', 'role:admin']], static function ($routes) {
    $routes->get('dashboard', 'Admin\DashboardController::index');
    $routes->match(['GET', 'POST'], 'account', 'Admin\AccountController::index');
    $routes->match(['GET', 'POST'], 'settings', 'Admin\SettingsController::index');
    $routes->get('audit', 'Admin\AuditController::index');

    // Seller Approvals
    $routes->get('sellers', 'Admin\SellerController::index');
    $routes->get('sellers/(:num)', 'Admin\SellerController::show/$1');
    $routes->post('sellers/(:num)/approve', 'Admin\SellerController::approve/$1');
    $routes->post('sellers/(:num)/reject', 'Admin\SellerController::reject/$1');

    // Categories Management
    $routes->get('categories', 'Admin\CategoryController::index');
    $routes->post('categories/store', 'Admin\CategoryController::store');
    $routes->post('categories/update/(:num)', 'Admin\CategoryController::update/$1');
    $routes->get('categories/delete/(:num)', 'Admin\CategoryController::delete/$1');

    // Platform-wide Product Catalog Moderation (all sellers)
    $routes->get('products', 'Admin\ProductController::index');
    $routes->post('products/(:num)/toggle', 'Admin\ProductController::toggleStatus/$1');

    // Admin's Own First-Party Products (CRUD)
    $routes->get('my-products', 'Admin\MyProductController::index');
    $routes->get('my-products/create', 'Admin\MyProductController::create');
    $routes->post('my-products/store', 'Admin\MyProductController::store');
    $routes->get('my-products/edit/(:num)', 'Admin\MyProductController::edit/$1');
    $routes->post('my-products/update/(:num)', 'Admin\MyProductController::update/$1');
    $routes->get('my-products/delete/(:num)', 'Admin\MyProductController::delete/$1');

    // Customers Directory
    $routes->get('customers', 'Admin\CustomerController::index');
    $routes->get('customers/(:num)', 'Admin\CustomerController::show/$1');
    $routes->post('customers/(:num)/toggle', 'Admin\CustomerController::toggleStatus/$1');

    // Orders Monitor & Full Status Override (Admin has root authority)
    $routes->get('my-orders', 'Admin\OrderController::mine');
    $routes->get('orders', 'Admin\OrderController::index');
    $routes->get('orders/(:num)', 'Admin\OrderController::show/$1');
    $routes->post('orders/(:num)/status', 'Admin\OrderController::updateStatus/$1');
    $routes->get('inbound', 'Admin\InboundController::index');
    $routes->post('inbound/(:num)/receive', 'Admin\InboundController::receive/$1');
    $routes->post('inbound/(:num)/status', 'Admin\InboundController::updateStatus/$1');

    // Commission Settings
    $routes->get('commissions', 'Admin\CommissionController::index');
    $routes->post('commissions', 'Admin\CommissionController::update');

    // Returns & Refunds
    $routes->get('returns', 'Admin\ReturnController::index');
    $routes->post('returns/(:num)/approve', 'Admin\ReturnController::approve/$1');
    $routes->post('returns/(:num)/reject', 'Admin\ReturnController::reject/$1');

    // Executive Reports
    $routes->get('reports', 'Admin\ReportController::index');
    $routes->get('coupons', 'Admin\CouponController::index');
    $routes->post('coupons/store', 'Admin\CouponController::store');
    $routes->get('banners', 'Admin\BannerController::index');
    $routes->post('banners/store', 'Admin\BannerController::store');
    $routes->get('banners/delete/(:num)', 'Admin\BannerController::delete/$1');
    $routes->get('flash-sales', 'Admin\FlashSaleController::index');
    $routes->post('flash-sales/store', 'Admin\FlashSaleController::store');
    $routes->post('flash-sales/item', 'Admin\FlashSaleController::addItem');
    $routes->post('flash-sales/item/(:num)/approve', 'Admin\FlashSaleController::approve/$1');
    $routes->post('flash-sales/item/(:num)/reject', 'Admin\FlashSaleController::reject/$1');
    $routes->get('shipping', 'Admin\ShippingController::index');
    $routes->post('shipping/store', 'Admin\ShippingController::store');
    $routes->get('shipping/delete/(:num)', 'Admin\ShippingController::delete/$1');
    $routes->get('payouts', 'Admin\PayoutController::index');
    $routes->post('payouts/(:num)/paid', 'Admin\PayoutController::markPaid/$1');
    $routes->get('payouts/(:num)/print', 'Admin\PayoutController::printPdf/$1');
    $routes->get('tickets', 'Admin\TicketController::index');
    $routes->get('tickets/(:num)', 'Admin\TicketController::show/$1');
    $routes->post('tickets/(:num)/reply', 'Admin\TicketController::reply/$1');
});

// --------------------------------------------------------------------
// API V1 RESTResource Routes (/api/v1/...)
// --------------------------------------------------------------------
$routes->group('api/v1', static function ($routes) {
    // Public Auth
    $routes->post('auth/register', 'Api\V1\AuthController::register');
    $routes->post('auth/verify-otp', 'Api\V1\AuthController::verifyOtp');
    $routes->post('auth/login', 'Api\V1\AuthController::login');

    // Public Catalog
    $routes->get('products', 'Api\V1\ProductsController::index');
    $routes->get('products/(:num)', 'Api\V1\ProductsController::show/$1');
    $routes->get('categories', 'Api\V1\CategoriesController::index');
    $routes->get('categories/(:segment)', 'Api\V1\CategoriesController::show/$1');

    // Cart (Session or Token)
    $routes->get('cart', 'Api\V1\CartController::index');
    $routes->post('cart/add', 'Api\V1\CartController::add');
    $routes->post('cart/update', 'Api\V1\CartController::updateItem');
    $routes->post('cart/remove', 'Api\V1\CartController::removeItem');

    // Guarded API Endpoints
    $routes->group('', ['filter' => 'api_auth'], static function ($routes) {
        $routes->get('orders', 'Api\V1\OrdersController::index');
        $routes->get('orders/(:num)', 'Api\V1\OrdersController::show/$1');
        $routes->post('orders/checkout', 'Api\V1\OrdersController::checkout');

        $routes->get('wallet/balance', 'Api\V1\WalletController::balance');
        $routes->get('wallet/transactions', 'Api\V1\WalletController::transactions');
    });
});
