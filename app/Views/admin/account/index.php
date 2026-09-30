<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<h4 class="fw-bold mb-2">Admin login</h4>
<p class="text-secondary small mb-4">Password plus email or phone opens the dashboard directly. No OTP email. After a database refresh, the seeder recreates this login.</p>
<div class="card-custom p-4" style="max-width:640px;">
    <form method="POST" action="<?= site_url('admin/account') ?>">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label small fw-bold">Name</label>
            <input type="text" name="name" class="form-control" value="<?= esc(old('name', $user['name'] ?? '')) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label small fw-bold">Username (email)</label>
            <input type="email" name="email" class="form-control" value="<?= esc(old('email', $user['email'] ?? '')) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label small fw-bold">Phone</label>
            <input type="text" name="phone" class="form-control" value="<?= esc(old('phone', $user['phone'] ?? '')) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label small fw-bold">New password</label>
            <input type="password" name="password" class="form-control" minlength="6" autocomplete="new-password" placeholder="Khali chhoro to purana password rahe">
        </div>
        <div class="mb-4">
            <label class="form-label small fw-bold">Confirm password</label>
            <input type="password" name="password_confirm" class="form-control" autocomplete="new-password">
        </div>
        <button class="btn btn-primary rounded-pill px-4" type="submit">Save login</button>
    </form>
</div>
<?= $this->endSection() ?>
