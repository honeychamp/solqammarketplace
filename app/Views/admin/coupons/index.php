<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Vouchers</h4>
</div>
<div class="card-custom p-4 mb-4">
    <form action="<?= site_url('admin/coupons/store') ?>" method="POST" class="row g-2 align-items-end">
        <?= csrf_field() ?>
        <div class="col-md-2"><label class="small fw-bold">Code</label><input name="code" class="form-control" required></div>
        <div class="col-md-2">
            <label class="small fw-bold">Type</label>
            <select name="type" class="form-select"><option value="percent">%</option><option value="fixed">Fixed PKR</option></select>
        </div>
        <div class="col-md-2"><label class="small fw-bold">Value</label><input type="number" step="0.01" name="value" class="form-control" required></div>
        <div class="col-md-2"><label class="small fw-bold">Min order</label><input type="number" step="0.01" name="min_order" class="form-control" value="0"></div>
        <div class="col-md-2"><label class="small fw-bold">Max uses</label><input type="number" name="max_uses" class="form-control"></div>
        <div class="col-md-2"><button class="btn btn-primary w-100">Create</button></div>
    </form>
</div>
<div class="card-custom p-4">
    <table class="table align-middle">
        <thead><tr><th>Code</th><th>Type</th><th>Value</th><th>Min / Max uses</th><th>Active</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($coupons as $c): ?>
            <tr>
                <td colspan="6" class="p-2">
                    <form action="<?= site_url('admin/coupons/update/' . $c['id']) ?>" method="POST" class="row g-2 align-items-end">
                        <?= csrf_field() ?>
                        <div class="col-md-2"><input name="code" class="form-control form-control-sm" value="<?= esc($c['code']) ?>" required></div>
                        <div class="col-md-2">
                            <select name="type" class="form-select form-select-sm">
                                <option value="percent" <?= ($c['type'] ?? '') === 'percent' ? 'selected' : '' ?>>%</option>
                                <option value="fixed" <?= ($c['type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Fixed PKR</option>
                            </select>
                        </div>
                        <div class="col-md-2"><input type="number" step="0.01" name="value" class="form-control form-control-sm" value="<?= esc($c['value']) ?>" required></div>
                        <div class="col-md-2"><input type="number" step="0.01" name="min_order" class="form-control form-control-sm" value="<?= esc($c['min_order'] ?? 0) ?>"></div>
                        <div class="col-md-1"><input type="number" name="max_uses" class="form-control form-control-sm" value="<?= esc($c['max_uses'] ?? '') ?>" placeholder="∞"></div>
                        <div class="col-md-1">
                            <select name="is_active" class="form-select form-select-sm">
                                <option value="1" <?= !empty($c['is_active']) ? 'selected' : '' ?>>On</option>
                                <option value="0" <?= empty($c['is_active']) ? 'selected' : '' ?>>Off</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex gap-1">
                            <button class="btn btn-sm btn-primary flex-grow-1">Save</button>
                            <a href="<?= site_url('admin/coupons/delete/' . $c['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this voucher?');">Delete</a>
                        </div>
                        <div class="col-12 small text-muted">Used <?= (int) $c['used_count'] ?> / <?= $c['max_uses'] ?? '∞' ?></div>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?= view('shared/_pager', ['pager' => $pager ?? null]) ?>
</div>
<?= $this->endSection() ?>
