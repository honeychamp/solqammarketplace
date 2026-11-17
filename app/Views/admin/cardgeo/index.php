<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php
$tab = $tab ?? 'divisions';
$flashOk = session()->getFlashdata('success');
$flashErr = session()->getFlashdata('error');
?>
<div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1">Card ID codes</h4>
        <p class="text-muted small mb-0">Customer <span class="font-monospace">01-01-C-12345678</span> · Merchant <span class="font-monospace">01-01-M-1234567-002</span>. Golden serials never auto-issue.</p>
    </div>
</div>
<?php if ($flashOk): ?><div class="alert alert-success rounded-4"><?= esc($flashOk) ?></div><?php endif; ?>
<?php if ($flashErr): ?><div class="alert alert-danger rounded-4"><?= esc($flashErr) ?></div><?php endif; ?>

<ul class="nav nav-pills mb-3 gap-1">
    <li class="nav-item"><a class="nav-link rounded-pill <?= $tab === 'divisions' ? 'active' : '' ?>" href="<?= site_url('admin/card-ids?tab=divisions') ?>">Divisions</a></li>
    <li class="nav-item"><a class="nav-link rounded-pill <?= $tab === 'cities' ? 'active' : '' ?>" href="<?= site_url('admin/card-ids?tab=cities') ?>">Cities</a></li>
    <li class="nav-item"><a class="nav-link rounded-pill <?= $tab === 'shops' ? 'active' : '' ?>" href="<?= site_url('admin/card-ids?tab=shops') ?>">Shop types</a></li>
    <li class="nav-item"><a class="nav-link rounded-pill <?= $tab === 'golden' ? 'active' : '' ?>" href="<?= site_url('admin/card-ids?tab=golden') ?>">Golden numbers</a></li>
</ul>

<?php if ($tab === 'divisions'): ?>
<div class="card border-0 shadow-sm rounded-4 p-3 mb-3">
    <form method="post" action="<?= site_url('admin/card-ids/division') ?>" class="row g-2 align-items-end">
        <?= csrf_field() ?>
        <div class="col-md-2"><label class="form-label small">Code</label><input name="code" class="form-control font-monospace" maxlength="2" value="<?= esc($nextDiv ?? '') ?>" required></div>
        <div class="col-md-4"><label class="form-label small">Division name</label><input name="name" class="form-control" placeholder="Lahore Division" required></div>
        <div class="col-md-3"><label class="form-label small">Province</label><input name="province_name" class="form-control" placeholder="Punjab" required></div>
        <div class="col-md-3"><button class="btn btn-solqam w-100">Add division</button></div>
    </form>
</div>
<div class="card border-0 shadow-sm rounded-4 p-3">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Edit</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($divisions as $d): ?>
                <tr>
                    <td>
                        <form method="post" action="<?= site_url('admin/card-ids/division/' . (int) $d['id'] . '/update') ?>" class="row g-2 align-items-center">
                            <?= csrf_field() ?>
                            <div class="col-md-2"><input name="code" class="form-control form-control-sm font-monospace" value="<?= esc($d['code']) ?>" maxlength="2"></div>
                            <div class="col-md-5"><input name="name" class="form-control form-control-sm" value="<?= esc($d['name']) ?>"></div>
                            <div class="col-md-3"><input name="province_name" class="form-control form-control-sm" value="<?= esc($d['province_name']) ?>"></div>
                            <div class="col-md-2"><button class="btn btn-sm btn-outline-primary w-100">Save</button></div>
                        </form>
                    </td>
                    <td class="text-nowrap" style="width:90px">
                        <form method="post" action="<?= site_url('admin/card-ids/division/' . (int) $d['id'] . '/delete') ?>" onsubmit="return confirm('Delete this division and its cities?');">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($tab === 'cities'): ?>
<div class="card border-0 shadow-sm rounded-4 p-3 mb-3">
    <form method="get" class="row g-2 mb-3">
        <input type="hidden" name="tab" value="cities">
        <div class="col-md-6">
            <select name="division_id" class="form-select" onchange="this.form.submit()">
                <option value="0">All divisions</option>
                <?php foreach ($divisions as $d): ?>
                    <option value="<?= (int) $d['id'] ?>" <?= (int) $divFilter === (int) $d['id'] ? 'selected' : '' ?>><?= esc($d['code'] . ' — ' . $d['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
    <form method="post" action="<?= site_url('admin/card-ids/city') ?>" class="row g-2 align-items-end">
        <?= csrf_field() ?>
        <div class="col-md-4">
            <label class="form-label small">Division</label>
            <select name="division_id" class="form-select" required>
                <option value="">Select division</option>
                <?php foreach ($divisions as $d): ?>
                    <option value="<?= (int) $d['id'] ?>" <?= (int) $divFilter === (int) $d['id'] ? 'selected' : '' ?>><?= esc($d['code'] . ' — ' . $d['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2"><label class="form-label small">City code</label><input name="code" class="form-control font-monospace" maxlength="2" placeholder="auto"></div>
        <div class="col-md-4"><label class="form-label small">City name</label><input name="name" class="form-control" required></div>
        <div class="col-md-2"><button class="btn btn-solqam w-100">Add city</button></div>
    </form>
</div>
<div class="card border-0 shadow-sm rounded-4 p-3">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Division</th><th>Edit</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($cities as $c): ?>
                <tr>
                    <td class="small"><?= esc(($c['division_code'] ?? '') . ' ' . ($c['division_name'] ?? '')) ?></td>
                    <td>
                        <form method="post" action="<?= site_url('admin/card-ids/city/' . (int) $c['id'] . '/update') ?>" class="row g-2 align-items-center">
                            <?= csrf_field() ?>
                            <div class="col-md-3"><input name="code" class="form-control form-control-sm font-monospace" value="<?= esc($c['code']) ?>" maxlength="2"></div>
                            <div class="col-md-7"><input name="name" class="form-control form-control-sm" value="<?= esc($c['name']) ?>"></div>
                            <div class="col-md-2"><button class="btn btn-sm btn-outline-primary w-100">Save</button></div>
                        </form>
                    </td>
                    <td>
                        <form method="post" action="<?= site_url('admin/card-ids/city/' . (int) $c['id'] . '/delete') ?>" onsubmit="return confirm('Delete this city?');">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($tab === 'shops'): ?>
<div class="card border-0 shadow-sm rounded-4 p-3 mb-3">
    <p class="small text-muted">Every garments shop in Pakistan shares the same 3-digit code (example 002). New merchants pick this on register.</p>
    <form method="post" action="<?= site_url('admin/card-ids/category') ?>" class="row g-2 align-items-end">
        <?= csrf_field() ?>
        <div class="col-md-2"><label class="form-label small">Code</label><input name="code" class="form-control font-monospace" maxlength="3" value="<?= esc($nextCat ?? '') ?>" required></div>
        <div class="col-md-7"><label class="form-label small">Shop type</label><input name="name" class="form-control" placeholder="Garments Store" required></div>
        <div class="col-md-3"><button class="btn btn-solqam w-100">Add shop type</button></div>
    </form>
</div>
<div class="card border-0 shadow-sm rounded-4 p-3">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Edit</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($categories as $cat): ?>
                <tr>
                    <td>
                        <form method="post" action="<?= site_url('admin/card-ids/category/' . (int) $cat['id'] . '/update') ?>" class="row g-2 align-items-center">
                            <?= csrf_field() ?>
                            <div class="col-md-2"><input name="code" class="form-control form-control-sm font-monospace" value="<?= esc($cat['code']) ?>" maxlength="3"></div>
                            <div class="col-md-8"><input name="name" class="form-control form-control-sm" value="<?= esc($cat['name']) ?>"></div>
                            <div class="col-md-2"><button class="btn btn-sm btn-outline-primary w-100">Save</button></div>
                        </form>
                    </td>
                    <td style="width:90px">
                        <form method="post" action="<?= site_url('admin/card-ids/category/' . (int) $cat['id'] . '/delete') ?>" onsubmit="return confirm('Delete this shop type?');">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($tab === 'golden'): ?>
<div class="card border-0 shadow-sm rounded-4 p-3 mb-3">
    <p class="small text-muted mb-3">These serials never appear on auto-issued cards. Gift them to your favourite customer or merchant.</p>
    <form method="post" action="<?= site_url('admin/card-ids/golden') ?>" class="row g-2 align-items-end">
        <?= csrf_field() ?>
        <div class="col-md-3">
            <label class="form-label small">Type</label>
            <select name="member_type" class="form-select" required>
                <option value="C">Customer (8 digits)</option>
                <option value="M">Merchant (7 digits)</option>
            </select>
        </div>
        <div class="col-md-3"><label class="form-label small">Serial</label><input name="serial" class="form-control font-monospace" placeholder="12345678" required></div>
        <div class="col-md-4"><label class="form-label small">Note</label><input name="note" class="form-control" placeholder="VIP"></div>
        <div class="col-md-2"><button class="btn btn-solqam w-100">Reserve</button></div>
    </form>
</div>
<div class="card border-0 shadow-sm rounded-4 p-3">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Type</th><th>Serial</th><th>Status</th><th>Gift to member</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($goldens as $g): ?>
                <tr>
                    <td><?= ($g['member_type'] ?? '') === 'M' ? 'Merchant' : 'Customer' ?></td>
                    <td class="font-monospace fw-bold"><?= esc($g['serial']) ?></td>
                    <td><?= esc($g['status']) ?><?php if (! empty($g['user_id'])): ?> · user #<?= (int) $g['user_id'] ?><?php endif; ?></td>
                    <td>
                        <?php if (($g['status'] ?? '') !== 'assigned'): ?>
                        <form method="post" action="<?= site_url('admin/card-ids/golden/' . (int) $g['id'] . '/assign') ?>" class="d-flex gap-2">
                            <?= csrf_field() ?>
                            <input name="member" class="form-control form-control-sm" placeholder="email or phone" required>
                            <button class="btn btn-sm btn-warning">Gift</button>
                        </form>
                        <?php else: ?>
                            Gifted
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (($g['status'] ?? '') !== 'assigned'): ?>
                        <form method="post" action="<?= site_url('admin/card-ids/golden/' . (int) $g['id'] . '/delete') ?>">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
<?= $this->endSection() ?>
