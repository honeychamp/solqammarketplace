<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between mb-3">
    <h4 class="fw-bold mb-0">Solqam Cards</h4>
    <a class="btn btn-outline-primary btn-sm rounded-pill" href="<?= merchant_admin_url('credits') ?>">Credit queue</a>
</div>
<div class="card border-0 shadow-sm rounded-4 p-3">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Holder</th><th>PAN</th><th>Scan ID</th><th>Tier</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach (($cards ?? []) as $c): ?>
                <tr>
                    <td><?= esc($c['holder_name'] ?: ($c['holder'] ?? '')) ?><div class="small text-muted"><?= esc($c['phone'] ?? '') ?></div></td>
                    <?php $parts = \App\Services\Card\SolqamCardNumber::decode((string) ($c['pan'] ?? '')); ?>
                    <td class="font-monospace">
                        <?= esc($c['pan'] ? \App\Services\Card\SolqamCardNumber::display((string) $c['pan']) : '—') ?>
                        <div class="small text-muted"><?= esc(($parts['type_name'] ?? '') . ' · ' . ($parts['city_name'] ?? '') . (! empty($parts['category_code']) ? ' · ' . $parts['category_code'] . ' ' . ($parts['category_name'] ?? '') : '')) ?></div>
                    </td>
                    <td class="font-monospace small"><?= esc($c['card_code']) ?></td>
                    <td><?= esc($c['tier'] ?? 'blue') ?></td>
                    <td><?= ! empty($c['frozen_at']) ? 'Frozen' : esc($c['status']) ?></td>
                    <td>
                        <form method="post" action="<?= site_url('admin/cards/' . (int) $c['id'] . '/freeze') ?>"><?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-dark"><?= ! empty($c['frozen_at']) ? 'Unfreeze' : 'Freeze' ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($cards)): ?>
                <tr><td colspan="6" class="text-muted">No cards issued yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?= $pager ? $pager->links() : '' ?>
</div>
<?= $this->endSection() ?>
