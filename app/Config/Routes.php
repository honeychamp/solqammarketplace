<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// --------------------------------------------------------------------
// Public Customer Web Routes
// --------------------------------------------------------------------
$routes->get('uploads/(:any)', 'MediaController::uploads/$1');
$routes->get('sitemap.xml', 'SeoController::sitemap');
$routes->get('.well-known/assetlinks.json', 'WellKnownController::assetlinks');
$routes->get('feed/meta.csv', 'FeedController::metaCsv');
$routes->get('compare', 'Customer\CatalogController::compare');
$routes->get('compare/toggle/(:num)', 'Customer\CatalogController::compareToggle/$1');
$routes->get('/', 'Customer\HomeController::index');
$routes->get('wishlist', 'Customer\AccountController::wishlist');
$routes->get('seller/register', 'Auth\AuthController::register');
$routes->get('shop', 'Customer\CatalogController::index');
$routes->get('flash-deals', 'Customer\CatalogController::flashDeals');
$routes->get('categories', 'Customer\CatalogController::index');
$routes->get('track', 'Customer\TrackController::index');
$routes->post('track', 'Customer\TrackController::index');
$routes->get('store/(:num)', 'Customer\StoreController::show/$1');
$routes->post('product/(:num)/question', 'Customer\CatalogController::askQuestion/$1', ['filter' => ['auth', 'role:customer']]);
$routes->post('wishlist/toggle', 'Customer\AccountController::toggleWishlist', ['filter' => 'auth']);
$routes->post('store/follow', 'Customer\AccountController::toggleFollow', ['filter' => 'auth']);
$routes->get('card/shops', 'Customer\CardController::shops');
$routes->get('card/c/(:segment)', 'Customer\CardController::cardLink/$1');
$routes->post('card/freeze', 'Customer\CardController::freeze', ['filter' => 'auth']);
$routes->post('card/pin', 'Customer\CardController::pin', ['filter' => 'auth']);
$routes->post('card/cvv', 'Customer\CardController::rotateCvv', ['filter' => 'auth']);
$routes->get('card', 'Customer\CardController::index');
$routes->get('geo/cities/(:segment)', 'GeoController::cities/$1');
$routes->match(['GET', 'POST'], 'merchant/join', 'Dukandar\JoinController::index', ['filter' => 'auth']);
$routes->match(['GET', 'POST'], 'dukandar/join', 'Dukandar\JoinController::index', ['filter' => 'auth']);
$routes->get('help', 'Customer\HelpController::index');
$routes->get('commission', 'Customer\PagesController::commission');
$routes->match(['GET', 'HEAD'], 'privacy', 'Customer\PagesController::privacy');
$routes->match(['GET', 'HEAD'], 'terms', 'Customer\PagesController::terms');
$routes->match(['GET', 'HEAD'], 'terms-and-conditions', 'Customer\PagesController::terms');
$routes->match(['GET', 'HEAD'], 'data-deletion', 'Customer\PagesController::dataDeletion');
$routes->get('seller-policies', 'Customer\PagesController::policies');
$routes->get('fulfillment', 'Customer\PagesController::fulfillment');
$routes->get('returns-policy', 'Customer\PagesController::returns');
$routes->get('shipping-info', 'Customer\PagesController::shipping');
$routes->get('product/(:num)', 'Customer\CatalogController::detail/$1');
$routes->get('cart', 'Customer\CartController::index');
$routes->post('cart/add', 'Customer\CartController::add');
$routes->post('cart/update', 'Customer\CartController::update');
$routes->post('cart/gift', 'Customer\CartController::gift');
$routes->get('cart/remove/(:num)', 'Customer\CartController::remove/$1');
$routes->get('guest', 'Customer\GuestController::start');
$routes->get('checkout', 'Customer\CheckoutController::index');
$routes->post('checkout', 'Customer\CheckoutController::process');
$routes->post('checkout/coupon', 'Customer\CheckoutController::applyCoupon');
$routes->post('marketing/lead', 'Customer\MarketingLeadController::capture');
$routes->get('checkout/shipping-quote', 'Customer\CheckoutController::shippingQuote');
$routes->get('checkout/pay/(:num)', 'Customer\CheckoutController::pay/$1');
$routes->get('order/thanks/(:num)', 'Customer\CheckoutController::thanks/$1');

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
$routes->get('auth/(:segment)/callback', 'Auth\AuthController::socialCallback/$1');
$routes->post('auth/(:segment)/token', 'Auth\AuthController::socialToken/$1');
$routes->get('auth/(:segment)', 'Auth\AuthController::socialStart/$1');

// --------------------------------------------------------------------
// Customer Account & Checkout Routes (Auth & Role Guarded)
// --------------------------------------------------------------------
$routes->match(['GET', 'POST'], 'payments/payfast/success', 'Payments\CallbackController::payfastSuccess');
$routes->match(['GET', 'POST'], 'payments/payfast/failure', 'Payments\CallbackController::payfastFailure');
$routes->post('payments/payfast/ipn', 'Payments\CallbackController::payfastIpn');

$routes->group('', ['filter' => ['auth', 'role:customer']], static function ($routes) {
    $routes->group('account', static function ($routes) {
        $routes->get('', 'Customer\AccountController::index');
        $routes->get('orders', 'Customer\OrderController::index');
        $routes->get('orders/(:num)', 'Customer\OrderController::show/$1');
        $routes->get('orders/(:num)/invoice', 'Customer\OrderController::invoice/$1');
        $routes->post('orders/(:num)/cancel', 'Customer\OrderController::cancel/$1');
        $routes->post('orders/(:num)/return', 'Customer\OrderController::requestReturn/$1');
        $routes->post('orders/(:num)/review', 'Customer\OrderController::submitReview/$1');
        $routes->get('wallet', 'Customer\AccountController::wallet');
        $routes->get('card', 'Customer\CardController::index');
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
    $routes->get('products/out-of-stock', 'Seller\ProductController::outOfStock');
    $routes->get('inventory', 'Seller\InventoryController::index');
    $routes->post('inventory/stock/(:num)', 'Seller\InventoryController::updateStock/$1');
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
    $routes->get('orders/export', 'Seller\OrderController::export');
    $routes->get('orders/(:num)', 'Seller\OrderController::show/$1');
    $routes->post('orders/(:num)/edit', 'Seller\OrderController::editPlaced/$1');
    $routes->post('orders/(:num)/status', 'Seller\OrderController::updateStatus/$1');
    $routes->post('orders/(:num)/fulfill', 'Seller\OrderController::setFulfill/$1');
    $routes->get('customers', 'Seller\CustomerController::index');
    $routes->get('customers/(:num)', 'Seller\CustomerController::show/$1');
    $routes->get('questions', 'Seller\HubController::questions');
    $routes->post('questions/(:num)/answer', 'Seller\HubController::answer/$1');
    $routes->get('payouts', 'Seller\HubController::payouts');
    $routes->get('payouts/statement', 'Seller\HubController::payoutStatement');
    $routes->get('dues', 'Seller\DueController::index');
    $routes->post('dues/proof-all', 'Seller\DueController::submitTotalProof');
    $routes->post('dues/(:num)/proof', 'Seller\DueController::submitProof/$1');
    $routes->get('campaigns', 'Seller\CampaignController::index');
    $routes->post('campaigns/join', 'Seller\CampaignController::join');
    $routes->get('products/csv-template', 'Seller\ProductController::csvTemplate');
    $routes->get('messages', 'Seller\ChatController::index');
    $routes->get('messages/(:num)', 'Seller\ChatController::show/$1');
    $routes->post('messages/(:num)/send', 'Seller\ChatController::send/$1');
});

$merchantHub = static function ($routes) {
    $routes->get('dashboard', 'Dukandar\HubController::dashboard');
    $routes->match(['GET', 'POST'], 'profile', 'Dukandar\HubController::profile');
    $routes->get('scan', 'Dukandar\HubController::scan');
    $routes->post('scan/lookup', 'Dukandar\HubController::lookup');
    $routes->post('scan/sale', 'Dukandar\HubController::sale');
    $routes->get('credits', 'Dukandar\HubController::credits');
    $routes->post('credits/proof-all', 'Dukandar\HubController::proofAll');
    $routes->post('credits/(:num)/proof', 'Dukandar\HubController::proof/$1');
    $routes->get('customers', 'Dukandar\HubController::customers');
    $routes->match(['GET', 'POST'], 'become-seller', 'Dukandar\HubController::becomeSeller');
};
$routes->group('merchant', ['filter' => ['auth', 'dukandar']], $merchantHub);
$routes->group('dukandar', ['filter' => ['auth', 'dukandar']], $merchantHub);

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
    $routes->get('dues', 'Admin\DueController::index');
    $routes->post('dues/(:num)/paid', 'Admin\DueController::markPaid/$1');
    $routes->get('dues/(:num)/receipt', 'Admin\DueController::receipt/$1');
    $routes->post('dues/seller/(:num)/paid-all', 'Admin\DueController::markPaidSeller/$1');
    $routes->post('dues/seller/(:num)/offset', 'Admin\DueController::offsetWallet/$1');

    // Categories Management
    $routes->get('categories', 'Admin\CategoryController::index');
    $routes->post('categories', 'Admin\CategoryController::store');
    $routes->post('categories/store', 'Admin\CategoryController::store');
    $routes->post('categories/update/(:num)', 'Admin\CategoryController::update/$1');
    $routes->get('categories/delete/(:num)', 'Admin\CategoryController::delete/$1');

    // Platform-wide Product Catalog Moderation (all sellers)
    $routes->get('products', 'Admin\ProductController::index');
    $routes->get('products/out-of-stock', 'Admin\ProductController::outOfStock');
    $routes->get('inventory', 'Admin\InventoryController::index');
    $routes->post('inventory/stock/(:num)', 'Admin\InventoryController::updateStock/$1');
    $routes->post('products/adjust-prices', 'Admin\ProductController::adjustPrices');
    $routes->get('products/edit/(:num)', 'Admin\ProductController::edit/$1');
    $routes->post('products/update/(:num)', 'Admin\ProductController::update/$1');
    $routes->get('products/delete/(:num)', 'Admin\ProductController::delete/$1');
    $routes->post('products/(:num)/toggle', 'Admin\ProductController::toggleStatus/$1');

    // Admin's Own First-Party Products (CRUD)
    $routes->get('my-products', 'Admin\MyProductController::index');
    $routes->get('my-products/out-of-stock', 'Admin\MyProductController::outOfStock');
    $routes->post('my-products/adjust-prices', 'Admin\MyProductController::adjustPrices');
    $routes->get('my-products/create', 'Admin\MyProductController::create');
    $routes->post('my-products/store', 'Admin\MyProductController::store');
    $routes->get('my-products/edit/(:num)', 'Admin\MyProductController::edit/$1');
    $routes->post('my-products/update/(:num)', 'Admin\MyProductController::update/$1');
    $routes->get('my-products/delete/(:num)', 'Admin\MyProductController::delete/$1');

    // Customers Directory
    $routes->get('merchants', 'Admin\DukandarController::index');
    $routes->get('merchants/credits', 'Admin\DukandarController::credits');
    $routes->get('merchants/(:num)', 'Admin\DukandarController::show/$1');
    $routes->post('merchants/(:num)/approve', 'Admin\DukandarController::approve/$1');
    $routes->post('merchants/(:num)/reject', 'Admin\DukandarController::reject/$1');
    $routes->post('merchants/credits/(:num)/release', 'Admin\DukandarController::release/$1');
    $routes->post('merchants/credits/(:num)/reject', 'Admin\DukandarController::rejectCredit/$1');
    $routes->post('merchants/(:num)/release-credits', 'Admin\DukandarController::releaseShop/$1');
    $routes->post('merchants/payouts/(:num)/approve', 'Admin\DukandarController::approvePayout/$1');
    $routes->post('merchants/payouts/(:num)/reject', 'Admin\DukandarController::rejectPayout/$1');
    $routes->get('dukandars', 'Admin\DukandarController::index');
    $routes->post('dukandars/(:num)/approve', 'Admin\DukandarController::approve/$1');
    $routes->post('dukandars/(:num)/reject', 'Admin\DukandarController::reject/$1');
    $routes->get('dukandars/credits', 'Admin\DukandarController::credits');
    $routes->post('dukandars/credits/(:num)/release', 'Admin\DukandarController::release/$1');
    $routes->post('dukandars/credits/(:num)/reject', 'Admin\DukandarController::rejectCredit/$1');
    $routes->post('dukandars/(:num)/release-credits', 'Admin\DukandarController::releaseShop/$1');
    $routes->post('dukandars/payouts/(:num)/approve', 'Admin\DukandarController::approvePayout/$1');
    $routes->post('dukandars/payouts/(:num)/reject', 'Admin\DukandarController::rejectPayout/$1');
    $routes->get('cards', 'Admin\DukandarController::cards');
    $routes->post('cards/(:num)/freeze', 'Admin\DukandarController::freezeCard/$1');
    $routes->get('card-ids', 'Admin\CardGeoController::index');
    $routes->post('card-ids/division', 'Admin\CardGeoController::storeDivision');
    $routes->post('card-ids/division/(:num)/update', 'Admin\CardGeoController::updateDivision/$1');
    $routes->post('card-ids/division/(:num)/delete', 'Admin\CardGeoController::deleteDivision/$1');
    $routes->post('card-ids/city', 'Admin\CardGeoController::storeCity');
    $routes->post('card-ids/city/(:num)/update', 'Admin\CardGeoController::updateCity/$1');
    $routes->post('card-ids/city/(:num)/delete', 'Admin\CardGeoController::deleteCity/$1');
    $routes->post('card-ids/category', 'Admin\CardGeoController::storeCategory');
    $routes->post('card-ids/category/(:num)/update', 'Admin\CardGeoController::updateCategory/$1');
    $routes->post('card-ids/category/(:num)/delete', 'Admin\CardGeoController::deleteCategory/$1');
    $routes->post('card-ids/golden', 'Admin\CardGeoController::storeGolden');
    $routes->post('card-ids/golden/(:num)/assign', 'Admin\CardGeoController::assignGolden/$1');
    $routes->post('card-ids/golden/(:num)/delete', 'Admin\CardGeoController::deleteGolden/$1');

    $routes->get('customers', 'Admin\CustomerController::index');
    $routes->get('customers/(:num)', 'Admin\CustomerController::show/$1');
    $routes->post('customers/(:num)/toggle', 'Admin\CustomerController::toggleStatus/$1');

    // Orders Monitor & Full Status Override (Admin has root authority)
    $routes->get('my-orders', 'Admin\OrderController::mine');
    $routes->get('orders', 'Admin\OrderController::index');
    $routes->get('orders/export', 'Admin\OrderController::export');
    $routes->get('my-orders/export', 'Admin\OrderController::export');
    $routes->get('orders/(:num)', 'Admin\OrderController::show/$1');
    $routes->post('orders/(:num)/edit', 'Admin\OrderController::editPlaced/$1');
    $routes->post('orders/(:num)/cancel-placed', 'Admin\OrderController::cancelPlaced/$1');
    $routes->post('orders/(:num)/confirm-call', 'Admin\OrderController::confirmCall/$1');
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
    $routes->post('coupons/update/(:num)', 'Admin\CouponController::update/$1');
    $routes->get('coupons/delete/(:num)', 'Admin\CouponController::delete/$1');
    $routes->get('banners', 'Admin\BannerController::index');
    $routes->post('banners/store', 'Admin\BannerController::store');
    $routes->post('banners/update/(:num)', 'Admin\BannerController::update/$1');
    $routes->get('banners/delete/(:num)', 'Admin\BannerController::delete/$1');
    $routes->get('flash-sales', 'Admin\FlashSaleController::index');
    $routes->post('flash-sales/store', 'Admin\FlashSaleController::store');
    $routes->post('flash-sales/update/(:num)', 'Admin\FlashSaleController::update/$1');
    $routes->get('flash-sales/delete/(:num)', 'Admin\FlashSaleController::delete/$1');
    $routes->post('flash-sales/item', 'Admin\FlashSaleController::addItem');
    $routes->post('flash-sales/item/(:num)/approve', 'Admin\FlashSaleController::approve/$1');
    $routes->post('flash-sales/item/(:num)/reject', 'Admin\FlashSaleController::reject/$1');
    $routes->get('flash-sales/item/(:num)/delete', 'Admin\FlashSaleController::deleteItem/$1');
    $routes->match(['GET', 'POST'], 'marketing', 'Admin\MarketingController::settings');
    $routes->get('marketing/logs', 'Admin\MarketingController::logs');
    $routes->post('marketing/test', 'Admin\MarketingController::testEvent');
    $routes->get('shipping', 'Admin\ShippingController::index');
    $routes->post('shipping/store', 'Admin\ShippingController::store');
    $routes->post('shipping/update/(:num)', 'Admin\ShippingController::update/$1');
    $routes->get('shipping/delete/(:num)', 'Admin\ShippingController::delete/$1');
    $routes->get('payouts', 'Admin\PayoutController::index');
    $routes->post('payouts/(:num)/paid', 'Admin\PayoutController::markPaid/$1');
    $routes->get('payouts/(:num)/print', 'Admin\PayoutController::printPdf/$1');
    $routes->get('tickets', 'Admin\TicketController::index');
    $routes->get('tickets/(:num)', 'Admin\TicketController::show/$1');
    $routes->post('tickets/(:num)/reply', 'Admin\TicketController::reply/$1');
    $routes->post('tickets/(:num)/update', 'Admin\TicketController::update/$1');
    $routes->get('tickets/(:num)/delete', 'Admin\TicketController::delete/$1');
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
