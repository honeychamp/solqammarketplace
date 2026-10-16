<?php

namespace App\Services\Auth;

use App\Models\SellerProfileModel;
use App\Models\UserModel;
use App\Services\Platform\SettingService;
use App\Services\Wallet\WalletService;
use Config\Database;
use RuntimeException;

class SocialAuthService
{
    public const PROVIDERS = ['google', 'facebook'];

    protected UserModel $users;
    protected SellerProfileModel $sellers;
    protected WalletService $wallets;
    protected AuthService $auth;
    protected $db;
    protected $session;

    public function __construct()
    {
        $this->users   = new UserModel();
        $this->sellers = new SellerProfileModel();
        $this->wallets = new WalletService();
        $this->auth    = new AuthService();
        $this->db      = Database::connect();
        $this->session = session();
    }

    public function isConfigured(string $provider): bool
    {
        if ($provider === 'google') {
            return SettingService::get('google_oauth_client_id') !== ''
                && SettingService::get('google_oauth_client_secret') !== '';
        }
        if ($provider === 'facebook') {
            return SettingService::get('facebook_app_id') !== ''
                && SettingService::get('facebook_app_secret') !== '';
        }

        return false;
    }

    public function startUrl(string $provider, string $intent = 'customer'): string
    {
        $provider = strtolower($provider);
        if (! in_array($provider, self::PROVIDERS, true)) {
            throw new RuntimeException('Unknown sign-in method.');
        }
        if (! $this->isConfigured($provider)) {
            throw new RuntimeException('This sign-in method is not set up yet. Use email, or ask Solqam admin to add Google / Facebook keys.');
        }

        $intent = $intent === 'seller' ? 'seller' : 'customer';
        $state  = bin2hex(random_bytes(16));
        $this->session->set('oauth_state', $state);
        $this->session->set('oauth_intent', $intent);
        $this->session->set('oauth_provider', $provider);

        $redirect = $this->callbackUrl($provider);

        if ($provider === 'google') {
            $query = http_build_query([
                'client_id'     => SettingService::get('google_oauth_client_id'),
                'redirect_uri'  => $redirect,
                'response_type' => 'code',
                'scope'         => 'openid email profile',
                'state'         => $state,
                'access_type'   => 'online',
                'prompt'        => 'select_account',
            ]);

            return 'https://accounts.google.com/o/oauth2/v2/auth?' . $query;
        }

        $query = http_build_query([
            'client_id'    => SettingService::get('facebook_app_id'),
            'redirect_uri' => $redirect,
            'state'        => $state,
            'scope'        => 'email,public_profile',
        ]);

        return 'https://www.facebook.com/v21.0/dialog/oauth?' . $query;
    }

    public function complete(string $provider, ?string $code, ?string $state): array
    {
        $provider = strtolower($provider);
        $savedState = (string) $this->session->get('oauth_state');
        $intent     = (string) $this->session->get('oauth_intent') === 'seller' ? 'seller' : 'customer';
        $this->session->remove(['oauth_state', 'oauth_intent', 'oauth_provider']);

        if ($savedState === '' || ! hash_equals($savedState, (string) $state)) {
            throw new RuntimeException('Sign-in expired. Please try Google or Facebook again.');
        }
        if ($code === null || $code === '') {
            throw new RuntimeException('Sign-in was cancelled.');
        }

        $profile = $provider === 'google' ? $this->fetchGoogle($code) : $this->fetchFacebook($code);
        $user    = $this->findOrCreate($provider, $profile, $intent);
        if (($user['role'] ?? '') === 'admin') {
            throw new RuntimeException('Administrator accounts cannot sign in here.');
        }
        if (($user['status'] ?? '') === 'suspended') {
            throw new RuntimeException('Your account has been suspended by the administrator.');
        }

        $this->auth->setSession($user);

        return $user;
    }

    public function callbackUrl(string $provider): string
    {
        return rtrim(site_url('auth/' . $provider . '/callback'), '/');
    }

    protected function fetchGoogle(string $code): array
    {
        $tokenRes = $this->http('POST', 'https://oauth2.googleapis.com/token', [
            'code'          => $code,
            'client_id'     => SettingService::get('google_oauth_client_id'),
            'client_secret' => SettingService::get('google_oauth_client_secret'),
            'redirect_uri'  => $this->callbackUrl('google'),
            'grant_type'    => 'authorization_code',
        ]);
        $token = json_decode($tokenRes['body'], true) ?: [];
        $access = (string) ($token['access_token'] ?? '');
        if ($access === '') {
            throw new RuntimeException('Google did not return an access token. Check Client ID, secret, and callback URL.');
        }

        $infoRes = $this->http('GET', 'https://www.googleapis.com/oauth2/v3/userinfo', [], [
            'Authorization: Bearer ' . $access,
        ]);
        $info = json_decode($infoRes['body'], true) ?: [];
        $email = strtolower(trim((string) ($info['email'] ?? '')));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Google did not share an email address.');
        }

        return [
            'id'     => (string) ($info['sub'] ?? ''),
            'email'  => $email,
            'name'   => trim((string) ($info['name'] ?? $info['given_name'] ?? 'Solqam shopper')) ?: 'Solqam shopper',
            'avatar' => (string) ($info['picture'] ?? ''),
        ];
    }

    protected function fetchFacebook(string $code): array
    {
        $tokenRes = $this->http('GET', 'https://graph.facebook.com/v21.0/oauth/access_token?' . http_build_query([
            'client_id'     => SettingService::get('facebook_app_id'),
            'client_secret' => SettingService::get('facebook_app_secret'),
            'redirect_uri'  => $this->callbackUrl('facebook'),
            'code'          => $code,
        ]));
        $token  = json_decode($tokenRes['body'], true) ?: [];
        $access = (string) ($token['access_token'] ?? '');
        if ($access === '') {
            throw new RuntimeException('Facebook did not return an access token. Check App ID, secret, and callback URL.');
        }

        $infoRes = $this->http('GET', 'https://graph.facebook.com/v21.0/me?' . http_build_query([
            'fields'       => 'id,name,email,picture.type(large)',
            'access_token' => $access,
        ]));
        $info  = json_decode($infoRes['body'], true) ?: [];
        $email = strtolower(trim((string) ($info['email'] ?? '')));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Facebook did not share an email. Allow email permission, or use Google / email sign-in.');
        }

        $avatar = (string) ($info['picture']['data']['url'] ?? '');

        return [
            'id'     => (string) ($info['id'] ?? ''),
            'email'  => $email,
            'name'   => trim((string) ($info['name'] ?? 'Solqam shopper')) ?: 'Solqam shopper',
            'avatar' => $avatar,
        ];
    }

    protected function findOrCreate(string $provider, array $profile, string $intent): array
    {
        $idCol = $provider === 'google' ? 'google_id' : 'facebook_id';
        $user  = null;

        if ($profile['id'] !== '' && $this->hasColumn($idCol)) {
            $user = $this->users->where($idCol, $profile['id'])->first();
        }
        if (! $user) {
            $user = $this->users->where('email', $profile['email'])->first();
        }

        if ($user) {
            $patch = [];
            if ($this->hasColumn($idCol) && empty($user[$idCol]) && $profile['id'] !== '') {
                $patch[$idCol] = $profile['id'];
            }
            if ($this->hasColumn('oauth_provider') && empty($user['oauth_provider'])) {
                $patch['oauth_provider'] = $provider;
            }
            if ($this->hasColumn('avatar_url') && $profile['avatar'] !== '' && empty($user['avatar_url'])) {
                $patch['avatar_url'] = $profile['avatar'];
            }
            if ((int) ($user['is_verified'] ?? 0) !== 1) {
                $patch['is_verified'] = 1;
            }
            if ($patch !== []) {
                $this->users->skipValidation(true)->update((int) $user['id'], $patch);
                $user = array_merge($user, $patch);
            }

            return $user;
        }

        $role = $intent === 'seller' ? 'seller' : 'customer';
        $now  = date('Y-m-d H:i:s');
        $row  = [
            'name'           => $profile['name'],
            'email'          => $profile['email'],
            'phone'          => $this->uniqueOauthPhone($provider, $profile['id'] . $profile['email']),
            'password_hash'  => password_hash(bin2hex(random_bytes(24)), PASSWORD_BCRYPT),
            'role'           => $role,
            'status'         => 'active',
            'is_verified'    => 1,
            'api_token'      => bin2hex(random_bytes(32)),
            'created_at'     => $now,
            'updated_at'     => $now,
        ];
        if ($this->hasColumn($idCol)) {
            $row[$idCol] = $profile['id'] !== '' ? $profile['id'] : null;
        }
        if ($this->hasColumn('oauth_provider')) {
            $row['oauth_provider'] = $provider;
        }
        if ($this->hasColumn('avatar_url') && $profile['avatar'] !== '') {
            $row['avatar_url'] = $profile['avatar'];
        }

        $insert = [];
        foreach ($row as $k => $v) {
            if ($this->hasColumn($k)) {
                $insert[$k] = $v;
            }
        }

        $this->db->table('users')->insert($insert);
        $userId = (int) $this->db->insertID();
        if ($userId < 1) {
            $err = $this->db->error();
            throw new RuntimeException($err['message'] ?: 'Could not create your account.');
        }

        $this->wallets->getOrCreateWallet($userId);

        if ($role === 'seller') {
            $store = trim($profile['name']) !== '' ? $profile['name'] . ' Store' : 'My Store';
            $this->sellers->skipValidation(true)->insert([
                'user_id'         => $userId,
                'store_name'      => $store,
                'approval_status' => 'pending',
            ]);
        }

        $created = $this->users->find($userId);
        if (! $created) {
            throw new RuntimeException('Account created but could not be loaded. Try signing in again.');
        }

        return $created;
    }

    protected function uniqueOauthPhone(string $provider, string $seed): string
    {
        $base = 'oauth:' . substr(hash('sha256', $provider . '|' . $seed), 0, 16);
        $phone = $base;
        $n = 0;
        while ($n < 20) {
            $exists = $this->db->table('users')->select('id')->where('phone', $phone)->get()->getFirstRow();
            if ($exists === null) {
                return $phone;
            }
            $n++;
            $phone = $base . $n;
        }

        return $base . bin2hex(random_bytes(2));
    }

    protected function hasColumn(string $column): bool
    {
        static $fields = null;
        if ($fields === null) {
            try {
                $fields = $this->db->getFieldNames('users');
            } catch (\Throwable $e) {
                $fields = [];
            }
        }

        return in_array($column, $fields, true);
    }

    /**
     * @param list<string> $headers
     * @return array{ok:bool,code:int,body:string}
     */
    protected function http(string $method, string $url, array $fields = [], array $headers = []): array
    {
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER     => array_merge(['Accept: application/json'], $headers),
        ];
        if (strtoupper($method) === 'POST') {
            $opts[CURLOPT_POST]       = true;
            $opts[CURLOPT_POSTFIELDS] = http_build_query($fields);
            $opts[CURLOPT_HTTPHEADER][] = 'Content-Type: application/x-www-form-urlencoded';
        }
        curl_setopt_array($ch, $opts);
        $raw  = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'ok'   => $raw !== false && $code >= 200 && $code < 300,
            'code' => $code,
            'body' => is_string($raw) ? $raw : '',
        ];
    }
}
