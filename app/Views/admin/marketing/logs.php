<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1">Marketing event logs</h4>
        <p class="text-secondary small mb-0">Browser queue plus Meta CAPI and TikTok Events API responses.</p>
    </div>
    <a class="btn btn-outline-dark btn-sm rounded-pill" href="<?= site_url('admin/marketing') ?>">Settings</a>
</div>
<form class="d-flex gap-2 mb-3" method="GET">
    <select name="status" class="form-select form-select-sm" style="width:140px">
        <option value="">All status</option>
        <?php foreach (['sent', 'error', 'queued'] as $st): ?>
            <option value="<?= $st ?>" <?= ($status ?? '') === $st ? 'selected' : '' ?>><?= $st ?></option>
        <?php endforeach; ?>
    </select>
    <select name="platform" class="form-select form-select-sm" style="width:180px">
        <option value="">All platforms</option>
        <?php foreach (['browser', 'meta_capi', 'tiktok_api'] as $p): ?>
            <option value="<?= $p ?>" <?= ($platform ?? '') === $p ? 'selected' : '' ?>><?= $p ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn btn-sm btn-dark">Filter</button>
</form>
<div class="card-custom p-0 overflow-auto">
    <table class="table align-middle mb-0 small">
        <thead class="table-light">
            <tr>
                <th>Time</th>
                <th>Platform</th>
                <th>Event</th>
                <th>event_id</th>
                <th>HTTP</th>
                <th>Status</th>
                <th>Payload / response</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="7" class="p-4 text-muted">No events yet. Enable tracking, save IDs, then browse the store or send a test event.</td></tr>
        <?php else: ?>
            <?php foreach ($rows as $row): ?>
                <tr class="<?= ($row['status'] ?? '') === 'error' ? 'table-danger' : '' ?>">
                    <td class="text-nowrap"><?= esc($row['created_at'] ?? '') ?></td>
                    <td><?= esc($row['platform'] ?? '') ?></td>
                    <td><?= esc($row['event_name'] ?? '') ?></td>
                    <td><code><?= esc($row['event_id'] ?? '') ?></code></td>
                    <td><?= esc((string) ($row['http_code'] ?? '')) ?></td>
                    <td>
                        <span class="badge bg-<?= ($row['status'] ?? '') === 'error' ? 'danger' : (($row['status'] ?? '') === 'queued' ? 'secondary' : 'success') ?>">
                            <?= esc($row['status'] ?? '') ?>
                        </span>
                    </td>
                    <td style="max-width:420px">
                        <details>
                            <summary>JSON</summary>
                            <pre class="small mb-1" style="white-space:pre-wrap"><?= esc(substr((string) ($row['payload'] ?? ''), 0, 4000)) ?></pre>
                            <pre class="small text-muted mb-0" style="white-space:pre-wrap"><?= esc(substr((string) ($row['response'] ?? ''), 0, 4000)) ?></pre>
                        </details>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?= view('shared/_pager', ['pager' => $pager ?? null, 'keep' => ['status', 'platform']]) ?>
<?= $this->endSection() ?>
