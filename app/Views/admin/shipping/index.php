<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="mb-4">
    <h4 class="fw-bold mb-1">City delivery charges</h4>
    <p class="text-secondary small mb-0">Set a rate per city. At checkout the customer’s address city is matched (Lahore = lahore) and that charge is applied automatically. Add a city named <code>Default</code> for any city you did not list.</p>
</div>
<div class="card-custom p-4 mb-4">
    <form action="<?= site_url('admin/shipping/store') ?>" method="POST" class="row g-2 align-items-end">
        <?= csrf_field() ?>
        <div class="col-md-2">
            <label class="form-label small">City</label>
            <input name="city" class="form-control" placeholder="Lahore" required>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Province</label>
            <input name="province" class="form-control" placeholder="Punjab">
        </div>
        <div class="col-md-2">
            <label class="form-label small">Delivery Rs.</label>
            <input type="number" step="1" min="0" name="rate" class="form-control" placeholder="250" required>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Free above Rs.</label>
            <input type="number" step="1" min="0" name="free_above" class="form-control" value="3000">
        </div>
        <div class="col-md-2">
            <label class="form-label small">ETA days</label>
            <input name="eta_days" class="form-control" placeholder="2-4">
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary w-100">Save city rate</button>
        </div>
    </form>
</div>
<div class="card-custom p-4">
    <?php if (empty($zones)): ?>
        <p class="text-muted mb-0">No cities yet. Add Lahore, Karachi, Islamabad, etc. Checkout uses these rates.</p>
    <?php else: ?>
        <table class="table align-middle">
            <thead class="table-light small">
                <tr>
                    <th>City</th>
                    <th>Province</th>
                    <th>Delivery</th>
                    <th>Free above</th>
                    <th>ETA</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($zones as $z): ?>
                <tr>
                    <td class="fw-semibold"><?= esc($z['city']) ?></td>
                    <td><?= esc($z['province']) ?></td>
                    <td>Rs. <?= number_format((float) $z['rate'], 0) ?></td>
                    <td>Rs. <?= number_format((float) $z['free_above'], 0) ?></td>
                    <td><?= esc($z['eta_days']) ?></td>
                    <td class="text-end">
                        <a href="<?= site_url('admin/shipping/delete/' . $z['id']) ?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Remove this city rate?');">Remove</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
