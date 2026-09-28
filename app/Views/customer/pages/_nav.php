<div class="list-group shadow-sm rounded-4 overflow-hidden mb-4">
    <a href="<?= site_url('commission') ?>" class="list-group-item list-group-item-action <?= uri_string() === 'commission' ? 'active' : '' ?>">Commission structure</a>
    <a href="<?= site_url('seller-policies') ?>" class="list-group-item list-group-item-action <?= uri_string() === 'seller-policies' ? 'active' : '' ?>">Seller policies</a>
    <a href="<?= site_url('fulfillment') ?>" class="list-group-item list-group-item-action <?= uri_string() === 'fulfillment' ? 'active' : '' ?>">Fulfillment by Solqam</a>
    <a href="<?= site_url('returns-policy') ?>" class="list-group-item list-group-item-action <?= uri_string() === 'returns-policy' ? 'active' : '' ?>">Returns &amp; refunds</a>
    <a href="<?= site_url('shipping-info') ?>" class="list-group-item list-group-item-action <?= uri_string() === 'shipping-info' ? 'active' : '' ?>">Shipping &amp; delivery</a>
    <a href="<?= site_url('help') ?>" class="list-group-item list-group-item-action">Help Center</a>
    <a href="<?= site_url('register?role=seller') ?>" class="list-group-item list-group-item-action">Become a seller</a>
</div>
