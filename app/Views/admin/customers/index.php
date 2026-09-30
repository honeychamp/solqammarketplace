<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Customer Management</h4>
        <p class="text-secondary small mb-0">Registered buyers and user status controls</p>
    </div>
</div>

<div class="card-custom p-4">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead class="table-light small">
                <tr>
                    <th>Customer Name</th>
                    <th>Email Address</th>
                    <th>Phone</th>
                    <th>Registered On</th>
                    <th>Wallet</th>
                    <th>Account Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $c): ?>
                    <tr>
                        <td>
                            <div class="fw-bold text-dark"><?= esc($c['name']) ?></div>
                            <small class="text-muted">ID: #<?= $c['id'] ?></small>
                        </td>
                        <td><?= esc($c['email']) ?></td>
                        <td><code><?= esc($c['phone']) ?></code></td>
                        <td><?= date('d M Y', strtotime($c['created_at'])) ?></td>
                        <td>Rs. <?= number_format((float) ($c['wallet_balance'] ?? 0), 0) ?></td>
                        <td>
                            <span class="badge bg-<?= ($c['status'] === 'active') ? 'success' : 'danger' ?>">
                                <?= ucfirst($c['status']) ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="<?= site_url('admin/customers/' . $c['id']) ?>" class="btn btn-primary btn-sm rounded-pill px-3 me-1">360</a>
                            <form action="<?= site_url('admin/customers/' . $c['id'] . '/toggle') ?>" method="POST" class="d-inline">
                                <?= csrf_field() ?>
                                <?php if ($c['status'] === 'active'): ?>
                                    <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="return confirm('Suspend this customer account?');">
                                        Suspend
                                    </button>
                                <?php else: ?>
                                    <button type="submit" class="btn btn-outline-success btn-sm rounded-pill px-3">
                                        Activate
                                    </button>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= view('shared/_pager', ['pager' => $pager ?? null]) ?>
</div>
<?= $this->endSection() ?>
