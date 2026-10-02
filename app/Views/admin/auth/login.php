<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Admin Console Login — Solqam Marketplace') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= base_url('assets/css/solqam-premium.css') ?>">
    <style>
        html, body { height: 100%; overflow: hidden; }
        body {
            margin: 0;
            height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            box-sizing: border-box;
            font-family: "Plus Jakarta Sans", system-ui, sans-serif;
            color: #0F172A;
            background:
                radial-gradient(ellipse 80% 60% at 10% 0%, rgba(11, 48, 230, 0.14), transparent 55%),
                radial-gradient(ellipse 70% 50% at 100% 100%, rgba(240, 20, 47, 0.08), transparent 50%),
                #EEF2FF;
        }
        .admin-login-wrap { width: min(400px, 100%); }
        .admin-login-card {
            background: #fff;
            border: 1px solid #E2E8F0;
            border-radius: 18px;
            box-shadow: 0 18px 40px -12px rgba(11, 48, 230, 0.16), 0 8px 20px rgba(15, 23, 42, 0.05);
            padding: 1.25rem 1.35rem 1.15rem;
        }
        .admin-logo { height: 46px; width: auto; }
        .admin-login-card .form-label {
            margin-bottom: 0.28rem;
            font-size: 0.78rem;
            font-weight: 600;
            color: #334155;
        }
        .admin-form-control {
            background: #F8FAFC !important;
            border: 1px solid #E2E8F0 !important;
            color: #0F172A !important;
            border-radius: 10px;
            padding: 0.48rem 0.85rem;
            font-size: 0.9rem;
        }
        .admin-form-control:focus {
            background: #fff !important;
            border-color: #0B30E6 !important;
            box-shadow: 0 0 0 3px rgba(11, 48, 230, 0.12) !important;
        }
        .admin-form-control::placeholder { color: #94A3B8; }
        .admin-input-group-text {
            background: #F8FAFC !important;
            border: 1px solid #E2E8F0 !important;
            color: #0B30E6 !important;
            padding: 0.48rem 0.7rem;
        }
        .admin-login-btn {
            background: linear-gradient(180deg, #2451F0 0%, #0B30E6 100%);
            border: 0;
            color: #fff;
            font-weight: 700;
            font-size: 0.9rem;
            padding: 0.55rem 1rem;
            border-radius: 999px;
            box-shadow: 0 8px 18px rgba(11, 48, 230, 0.28);
        }
        .admin-login-btn:hover { color: #fff; filter: brightness(1.05); }
        .admin-chip {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #EEF2FF;
            color: #0B30E6;
            border: 1px solid #C7D2FE;
            font-size: 0.62rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            border-radius: 999px;
            padding: 0.2rem 0.55rem;
        }
        .admin-back { color: #64748B; font-size: 0.8rem; text-decoration: none; }
        .admin-back:hover { color: #0B30E6; }
        .admin-login-card .alert { margin-bottom: 0.65rem; padding: 0.45rem 0.7rem; }
    </style>
</head>
<body>
<div class="admin-login-wrap">
    <div class="admin-login-card">
        <div class="text-center mb-3">
            <img src="<?= base_url('assets/images/solqam-logo.svg') ?>?v=20261002a" alt="Solqam Market Place" class="admin-logo mb-2">
            <div class="mb-2"><span class="admin-chip"><i class="bi bi-shield-lock-fill"></i> Admin only</span></div>
            <h1 class="fw-bold mb-0" style="font-size: 1.15rem; color: #0F172A;">Admin Console</h1>
            <p class="mb-0 mt-1" style="font-size: 0.78rem; color: #64748B;">Sign in to manage Solqam</p>
        </div>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger small rounded-3 border-0 d-flex align-items-center gap-2 mb-2" style="background: #FEF2F2; color: #B91C1C;">
                <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                <div><?= session()->getFlashdata('error') ?></div>
            </div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('info')): ?>
            <div class="alert small rounded-3 border-0 d-flex align-items-center gap-2 mb-2" style="background: #EEF2FF; color: #1E3A8A;">
                <i class="bi bi-info-circle-fill flex-shrink-0"></i>
                <div><?= session()->getFlashdata('info') ?></div>
            </div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert small rounded-3 border-0 d-flex align-items-center gap-2 mb-2" style="background: #ECFDF5; color: #047857;">
                <i class="bi bi-check-circle-fill flex-shrink-0"></i>
                <div><?= session()->getFlashdata('success') ?></div>
            </div>
        <?php endif; ?>

        <form action="<?= site_url('admin/login') ?>" method="POST">
            <?= csrf_field() ?>
            <div class="mb-2">
                <label class="form-label">Email or username</label>
                <div class="input-group">
                    <span class="input-group-text admin-input-group-text border-end-0"><i class="bi bi-person-fill"></i></span>
                    <input type="text" name="login" class="form-control admin-form-control border-start-0 ps-0" placeholder="admin@solqam.pk" value="<?= old('login') ?>" required autofocus>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text admin-input-group-text border-end-0"><i class="bi bi-key-fill"></i></span>
                    <input type="password" name="password" id="admin_password" class="form-control admin-form-control border-start-0 border-end-0 ps-0" placeholder="••••••••" required>
                    <button class="btn btn-outline-secondary border-start-0 admin-input-group-text" type="button" onclick="togglePasswordVisibility('admin_password', 'adminPasswordEye')" style="color:#64748B;">
                        <i class="bi bi-eye" id="adminPasswordEye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn admin-login-btn w-100">
                <i class="bi bi-box-arrow-in-right me-1"></i> Sign in
            </button>
        </form>
    </div>
    <div class="mt-3 text-center">
        <a href="<?= site_url('/') ?>" class="admin-back"><i class="bi bi-arrow-left"></i> Back to storefront</a>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePasswordVisibility(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (!input || !icon) return;
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}
</script>
</body>
</html>
