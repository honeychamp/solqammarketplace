<?php

declare(strict_types=1);

/**
 * Receives Google/Facebook OAuth GET (blocked by ModSecurity on CI4 routes)
 * then redirects to /auth/{provider}/callback?k=… without encoded URLs.
 */
function solqam_oauth_catch(string $provider): void
{
    $provider = strtolower($provider);
    if (! in_array($provider, ['google', 'facebook'], true)) {
        http_response_code(400);
        echo 'Unknown sign-in method.';
        exit;
    }

    $root = dirname(__DIR__, 2);
    $dir  = $root . DIRECTORY_SEPARATOR . 'writable' . DIRECTORY_SEPARATOR . 'oauth';
    if (! is_dir($dir) && ! @mkdir($dir, 0750, true) && ! is_dir($dir)) {
        http_response_code(500);
        echo 'Could not store sign-in. Upload writable/oauth on the server.';
        exit;
    }

    $token = bin2hex(random_bytes(16));
    $payload = [
        'provider' => $provider,
        'code'     => isset($_GET['code']) ? (string) $_GET['code'] : '',
        'state'    => isset($_GET['state']) ? (string) $_GET['state'] : '',
        'error'    => isset($_GET['error']) ? (string) $_GET['error'] : '',
        'exp'      => time() + 300,
    ];
    $file = $dir . DIRECTORY_SEPARATOR . $token . '.json';
    if (@file_put_contents($file, json_encode($payload)) === false) {
        http_response_code(500);
        echo 'Could not store sign-in token.';
        exit;
    }

    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? 'solqam.com'));
    $host = preg_replace('/^www\./', '', $host) ?: $host;
    $https = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || str_ends_with($host, 'solqam.com');
    $url = ($https ? 'https' : 'http') . '://' . $host . '/auth/' . $provider . '/callback?k=' . rawurlencode($token);

    header('Location: ' . $url, true, 302);
    exit;
}
