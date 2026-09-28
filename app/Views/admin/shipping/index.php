<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<h4 class="fw-bold mb-4">Shipping Zones</h4>
<div class="card-custom p-4 mb-4">
    <form action="<?= site_url('admin/shipping/store') ?>" method="POST" class="row g-2">
        <?= csrf_field() ?>
        <div class="col-md-2"><input name="city" class="form-control" placeholder="City" required></div>
        <div class="col-md-2"><input name="province" class="form-control" placeholder="Province"></div>
        <div class="col-md-2"><input type="number" step="0.01" name="rate" class="form-control" placeholder="Rate" required></div>
        <div class="col-md-2"><input type="number" step="0.01" name="free_above" class="form-control" placeholder="Free above" value="3000"></div>
        <div class="col-md-2"><input name="eta_days" class="form-control" placeholder="2-4"></div>
        <div class="col-md-2"><button class="btn btn-primary w-100">Add zone</button></div>
    </form>
</div>
<div class="card-custom p-4">
    <table class="table">
        <thead><tr><th>City</th><th>Province</th><th>Rate</th><th>Free above</th><th>ETA</th></tr></thead>
        <tbody>
        <?php foreach ($zones as $z): ?>
            <tr>
                <td><?= esc($z['city']) ?></td>
                <td><?= esc($z['province']) ?></td>
                <td>Rs. <?= number_format($z['rate'], 0) ?></td>
                <td>Rs. <?= number_format($z['free_above'], 0) ?></td>
                <td><?= esc($z['eta_days']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= $this->endSection() ?>
