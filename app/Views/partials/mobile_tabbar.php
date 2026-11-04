<?php
$tabUri = trim((string) uri_string(), '/');
if ($tabUri === 'checkout' || str_starts_with($tabUri, 'checkout')) {
    return;
}
$tabUser = $user ?? session()->get('user');
$tabRole = (string) ($tabUser['role'] ?? '');
$tabLogged = ! empty($tabUser['id']);
if ($tabRole === 'admin') {
    $accountHref = site_url('admin/dashboard');
} elseif ($tabRole === 'seller') {
    $accountHref = site_url('seller/dashboard');
} elseif ($tabLogged) {
    $accountHref = site_url('account');
} else {
    $accountHref = site_url('login');
}
$wishHref = $tabLogged && $tabRole === 'customer' ? site_url('account/wishlist') : site_url('login');
$isHome = $tabUri === '';
$isShop = $tabUri === 'shop' || str_starts_with($tabUri, 'shop') || str_starts_with($tabUri, 'product/') || $tabUri === 'flash-deals' || str_starts_with($tabUri, 'store/');
$isCart = $tabUri === 'cart' || str_starts_with($tabUri, 'cart/');
$isWish = str_contains($tabUri, 'wishlist');
$isAccount = str_starts_with($tabUri, 'account') && ! $isWish
    || $tabUri === 'login' || $tabUri === 'register'
    || str_starts_with($tabUri, 'seller/dashboard')
    || str_starts_with($tabUri, 'admin/dashboard');
$tabCart = (int) ($cartCount ?? 0);
$tabWish = function_exists('wishlist_count') ? wishlist_count() : 0;
?>
<nav class="solqam-tabbar d-md-none" aria-label="Solqam menu">
    <a class="solqam-tab<?= $isHome ? ' is-on' : '' ?>" href="<?= site_url('/') ?>">
        <i class="bi <?= $isHome ? 'bi-house-door-fill' : 'bi-house-door' ?>"></i>
        <span>Home</span>
    </a>
    <a class="solqam-tab<?= $isShop ? ' is-on' : '' ?>" href="<?= site_url('shop') ?>">
        <i class="bi <?= $isShop ? 'bi-grid-3x3-gap-fill' : 'bi-grid-3x3-gap' ?>"></i>
        <span>Shop</span>
    </a>
    <a class="solqam-tab<?= $isCart ? ' is-on' : '' ?>" href="<?= site_url('cart') ?>">
        <span class="solqam-tab-ico">
            <i class="bi <?= $isCart ? 'bi-cart-fill' : 'bi-cart3' ?>"></i>
            <?php if ($tabCart > 0): ?><em><?= $tabCart > 9 ? '9+' : $tabCart ?></em><?php endif; ?>
        </span>
        <span>Cart</span>
    </a>
    <a class="solqam-tab<?= $isWish ? ' is-on' : '' ?>" href="<?= $wishHref ?>">
        <span class="solqam-tab-ico">
            <i class="bi <?= $isWish ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
            <?php if ($tabWish > 0): ?><em><?= $tabWish > 9 ? '9+' : $tabWish ?></em><?php endif; ?>
        </span>
        <span>Wishlist</span>
    </a>
    <a class="solqam-tab<?= $isAccount ? ' is-on' : '' ?>" href="<?= $accountHref ?>">
        <i class="bi <?= $isAccount ? 'bi-person-circle' : 'bi-person' ?>"></i>
        <span>Account</span>
    </a>
</nav>
