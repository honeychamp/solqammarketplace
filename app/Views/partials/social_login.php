<?php
$intent   = ($intent ?? 'customer') === 'seller' ? 'seller' : 'customer';
$googleId = \App\Services\Platform\SettingService::get('google_oauth_client_id');
$fbId     = \App\Services\Platform\SettingService::get('facebook_app_id');
?>
<div class="social-login-stack mb-4" data-social-intent="<?= esc($intent) ?>">
    <?= csrf_field() ?>
    <?php if ($fbId !== ''): ?>
    <?php $q = $intent === 'seller' ? '?intent=seller' : ''; ?>
    <a href="<?= site_url('auth/facebook' . $q) ?>" class="btn btn-social-facebook w-100">
        <i class="bi bi-facebook fs-5"></i>
        <span>Continue with Facebook</span>
    </a>
    <?php endif; ?>
    <?php if ($googleId !== ''): ?>
    <button type="button" class="btn btn-social-google w-100 solqam-google-btn" data-google-client="<?= esc($googleId) ?>">
        <svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true"><path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.3 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.1 8 3l5.7-5.7C34.2 6.1 29.4 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.2-.1-2.3-.4-3.5z"/><path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 16 19 12 24 12c3.1 0 5.8 1.1 8 3l5.7-5.7C34.2 6.1 29.4 4 24 4 16.3 4 9.6 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.2 0 10-2 13.6-5.2l-6.3-5.3C29.2 35.1 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-7.9l-6.5 5C9.5 39.6 16.2 44 24 44z"/><path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-1.1 3.2-3.5 5.7-6.6 7.1l6.3 5.3C38.2 37.3 44 31.5 44 24c0-1.2-.1-2.3-.4-3.5z"/></svg>
        <span>Continue with Google</span>
    </button>
    <?php endif; ?>
    <div class="social-login-or"><span>or</span></div>
</div>
<?php if (empty($GLOBALS['solqam_social_js'])): $GLOBALS['solqam_social_js'] = true; ?>
<?php if ($googleId !== ''): ?>
<script src="https://accounts.google.com/gsi/client" async defer></script>
<?php endif; ?>
<script>
(function () {
    var csrfName = <?= json_encode(csrf_token()) ?>;
    var authBase = <?= json_encode(rtrim(site_url('auth'), '/')) ?>;

    function fail(msg) {
        alert(msg || 'Sign-in failed. Try email login.');
    }

    function postToken(stack, provider, accessToken) {
        var csrfInput = stack.querySelector('input[name="' + csrfName + '"]');
        var intent = stack.getAttribute('data-social-intent') || 'customer';
        var body = new FormData();
        if (csrfInput) body.append(csrfName, csrfInput.value);
        body.append('intent', intent);
        body.append('access_token', accessToken);
        return fetch(authBase + '/' + provider + '/token', {
            method: 'POST',
            body: body,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        }).then(function (res) {
            return res.json().then(function (data) {
                if (data && data.redirect) {
                    window.location.href = data.redirect;
                    return;
                }
                fail((data && data.error) || 'Sign-in failed.');
            });
        }).catch(function () { fail('Network error. Try again.'); });
    }

    function bindStack(stack) {
        if (!stack || stack.getAttribute('data-social-bound') === '1') return;
        stack.setAttribute('data-social-bound', '1');

        var gBtn = stack.querySelector('.solqam-google-btn');
        if (gBtn) {
            gBtn.addEventListener('click', function () {
                var googleClient = gBtn.getAttribute('data-google-client') || '';
                if (!window.google || !google.accounts || !google.accounts.oauth2) {
                    fail('Google sign-in is still loading. Wait a second and try again.');
                    return;
                }
                google.accounts.oauth2.initTokenClient({
                    client_id: googleClient,
                    scope: 'openid email profile',
                    callback: function (resp) {
                        if (!resp || !resp.access_token) {
                            fail('Google sign-in was cancelled.');
                            return;
                        }
                        postToken(stack, 'google', resp.access_token);
                    }
                }).requestAccessToken();
            });
        }

    }

    function boot() {
        document.querySelectorAll('.social-login-stack').forEach(bindStack);
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
</script>
<?php endif; ?>
