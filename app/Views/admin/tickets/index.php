<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<h4 class="fw-bold mb-3">Help tickets</h4>
<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <table class="table mb-0 align-middle">
        <thead class="table-light"><tr><th>ID</th><th>Customer</th><th>Subject</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($tickets as $t): ?>
            <tr>
                <td>#<?= $t['id'] ?></td>
                <td><?= esc($t['customer_name'] ?? '') ?><div class="small text-muted"><?= esc($t['customer_email'] ?? '') ?></div></td>
                <td><?= esc($t['subject']) ?></td>
                <td><span class="badge bg-secondary"><?= esc($t['status']) ?></span></td>
                <td><a href="<?= site_url('admin/tickets/' . $t['id']) ?>" class="btn btn-sm btn-outline-primary">Open</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($tickets)): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">No tickets.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?= $this->endSection() ?>
