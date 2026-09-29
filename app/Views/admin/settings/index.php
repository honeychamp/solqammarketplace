<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<h4 class="fw-bold mb-3">Seller SLA &amp; fraud limits</h4>
<form method="POST" class="card-custom p-4" style="max-width:640px;">
    <?= csrf_field() ?>
    <div class="mb-3"><label class="form-label">Confirm within (hours)</label><input type="number" name="sla_confirm_hours" class="form-control" value="<?= esc($sla_confirm_hours) ?>"></div>
    <div class="mb-3"><label class="form-label">Ship within (hours)</label><input type="number" name="sla_ship_hours" class="form-control" value="<?= esc($sla_ship_hours) ?>"></div>
    <div class="mb-3"><label class="form-label">Max open COD orders per customer</label><input type="number" name="cod_max_open" class="form-control" value="<?= esc($cod_max_open) ?>"></div>
    <div class="mb-3"><label class="form-label">Login attempts / 15 min / IP</label><input type="number" name="auth_max_hits" class="form-control" value="<?= esc($auth_max_hits) ?>"></div>
    <button class="btn btn-primary rounded-pill">Save</button>
</form>
<?= $this->endSection() ?>
