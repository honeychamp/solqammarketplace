<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<h4 class="fw-bold mb-3">Audit log</h4>
<div class="card-custom p-3 table-responsive">
    <table class="table table-sm">
        <thead><tr><th>When</th><th>User</th><th>Action</th><th>Entity</th><th>IP</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= esc($r['created_at']) ?></td>
                <td><?= esc($r['user_id']) ?></td>
                <td><?= esc($r['action']) ?></td>
                <td><?= esc($r['entity']) ?> #<?= esc($r['entity_id']) ?></td>
                <td><?= esc($r['ip_address']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= $this->endSection() ?>
