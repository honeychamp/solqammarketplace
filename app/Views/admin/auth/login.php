<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Admin Console Login — Solqam Marketplace') ?></title>
    
    <!-- Google Fonts & Bootstrap -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= base_url('assets/css/solqam-premium.css') ?>">

    <style>
        body {
            background-color: #0A0F1D;
            background-image: 
                radial-gradient(at 15% 15%, rgba(11, 48, 230, 0.18) 0px, transparent 50%),
                radial-gradient(at 85% 85%, rgba(240, 20, 47, 0.12) 0px, transparent 50%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: var(--sol-font);
            color: #F8FAFC;
        }

        .admin-login-card {
            background: rgba(18, 26, 47, 0.92);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            box-shadow: 0 24px 48px rgba(0, 0, 0, 0.4);
            max-width: 460px;
            width: 100%;
        }

        .admin-form-control {
            background-color: rgba(10, 15, 29, 0.8) !important;
            border: 1px solid rgba(255, 255, 255, 0.12) !important;
            color: #FFFFFF !important;
            border-radius: 10px;
            padding: 0.68rem 1rem;
        }

        .admin-form-control:focus {
            border-color: var(--sol-primary) !important;
            box-shadow: 0 0 0 4px rgba(11, 48, 230, 0.25) !important;
            background-color: rgba(10, 15, 29, 0.95) !important;
        }

        .admin-form-control::placeholder {
            color: #64748B;
        }

        .admin-input-group-text {
            background-color: rgba(10, 15, 29, 0.8) !important;
            border: 1px solid rgba(255, 255, 255, 0.12) !important;
            color: #94A3B8 !important;
            border-radius: 10px 0 0 10px;
        }

        .admin-brand-glow {
            filter: drop-shadow(0 4px 16px rgba(11, 48, 230, 0.35));
        }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5 d-flex flex-column align-items-center">

            <!-- Card Box -->
            <div class="admin-login-card p-4 p-sm-5">
                <!-- Brand Header -->
                <div class="text-center mb-4">
                    <div class="mb-3">
                        <img src="<?= base_url('assets/images/solqam-logo-light.svg') ?>" alt="Solqam Market Place" height="54" class="admin-brand-glow">
                    </div>
                    <div class="d-inline-flex align-items-center gap-1.5 px-3 py-1 rounded-pill small mb-2" style="background: rgba(240, 20, 47, 0.15); color: #FF4D61; border: 1px solid rgba(240, 20, 47, 0.25); font-size: 0.72rem; letter-spacing: 0.5px; font-weight: 700; text-uppercase;">
                        <i class="bi bi-shield-lock-fill"></i> RESTRICTED ACCESS &bull; GOVERNANCE ONLY
                    </div>
                    <h4 class="fw-bold text-white mb-1">Platform Admin Console</h4>
                    <p class="text-secondary small mb-0">Multi-vendor marketplace oversight &amp; root control</p>
                </div>

                <!-- Alert Messages -->
                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger py-2.5 small rounded-3 border-0 mb-4 d-flex align-items-center gap-2" style="background: rgba(239, 68, 68, 0.2); color: #FCA5A5; border: 1px solid rgba(239, 68, 68, 0.3) !important;">
                        <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0"></i>
                        <div><?= session()->getFlashdata('error') ?></div>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('info')): ?>
                    <div class="alert alert-info py-2.5 small rounded-3 border-0 mb-4 d-flex align-items-center gap-2" style="background: rgba(59, 130, 246, 0.2); color: #93C5FD; border: 1px solid rgba(59, 130, 246, 0.3) !important;">
                        <i class="bi bi-info-circle-fill fs-5 flex-shrink-0"></i>
                        <div><?= session()->getFlashdata('info') ?></div>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('success')): ?>
                    <div class="alert alert-success py-2.5 small rounded-3 border-0 mb-4 d-flex align-items-center gap-2" style="background: rgba(16, 185, 129, 0.2); color: #6EE7B7; border: 1px solid rgba(16, 185, 129, 0.3) !important;">
                        <i class="bi bi-check-circle-fill fs-5 flex-shrink-0"></i>
                        <div><?= session()->getFlashdata('success') ?></div>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form action="<?= site_url('admin/login') ?>" method="POST">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-light" style="font-size: 0.82rem;">Administrator Email or Username</label>
                        <div class="input-group">
                            <span class="input-group-text admin-input-group-text border-end-0"><i class="bi bi-person-fill-lock"></i></span>
                            <input type="text" name="login" class="form-control admin-form-control border-start-0 ps-0" placeholder="Enter admin email or username" value="<?= old('login') ?>" required autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-semibold text-light mb-0" style="font-size: 0.82rem;">Security Key / Password</label>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text admin-input-group-text border-end-0"><i class="bi bi-key-fill"></i></span>
                            <input type="password" name="password" id="admin_password" class="form-control admin-form-control border-start-0 border-end-0 ps-0" placeholder="••••••••" required>
                            <button class="btn btn-outline-secondary border-start-0 admin-input-group-text text-secondary" type="button" onclick="togglePasswordVisibility('admin_password', 'adminPasswordEye')">
                                <i class="bi bi-eye" id="adminPasswordEye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-sol-primary w-100 py-2.5 rounded-pill fw-bold mb-3 shadow-lg" style="font-size: 0.95rem;">
                        <i class="bi bi-shield-check me-1"></i> Sign In to Admin Console
                    </button>
                </form>
            </div>

            <!-- Footer Return Link -->
            <div class="mt-4 text-center">
                <a href="<?= site_url('/') ?>" class="text-secondary text-decoration-none small d-inline-flex align-items-center gap-1" style="transition: color 0.2s ease;">
                    <i class="bi bi-arrow-left"></i> Return to SOLQAM Storefront
                </a>
            </div>

        </div>
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
