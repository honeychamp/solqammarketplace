<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Services\Auth\AuthService;
use Exception;

class AuthController extends BaseController
{
    protected AuthService $authService;
    protected UserModel $userModel;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->userModel   = new UserModel();
    }

    public function login()
    {
        if ($this->authService->isLoggedIn()) {
            $role = session()->get('user.role');
            if ($role === 'admin') return redirect()->to('/admin/dashboard');
            if ($role === 'seller') return redirect()->to('/seller/dashboard');
            return redirect()->to('/');
        }

        if ($this->request->is('post')) {
            if (! \App\Services\Auth\RateLimitService::hit('login', \App\Services\Platform\SettingService::int('auth_max_hits', 8))) {
                return redirect()->back()->withInput()->with('error', 'Too many login attempts. Try again in 15 minutes.');
            }
            $rules = [
                'login'    => 'required',
                'password' => 'required',
            ];

            if (!$this->validate($rules)) {
                return redirect()->back()->withInput()->with('error', 'Please enter your email/phone and password.');
            }

            $login    = $this->request->getPost('login');
            $password = $this->request->getPost('password');

            try {
                $user = $this->authService->login($login, $password);

                if ($user['role'] === 'admin') {
                    // Admins cannot sign in from public customer/seller login
                    $this->authService->logout();
                    return redirect()->back()->withInput()->with('error', 'Access denied. Administrator accounts cannot log in here. Only customer and seller accounts are permitted.');
                }

                if ($user['role'] === 'seller') {
                    session()->remove('pending_login_email');
                    $approved = seller_is_approved((int) $user['id']);
                    $msg = $approved
                        ? 'Welcome to your Seller Dashboard!'
                        : 'Signed in. Your store is pending admin approval — you can open the dashboard, but you cannot manage products until Solqam approves you.';

                    return redirect()->to('/seller/dashboard')->with('success', $msg);
                }

                $redirectUrl = session()->get('redirect_url') ?? '/';
                session()->remove('redirect_url');
                session()->remove('pending_login_email');
                return redirect()->to($redirectUrl)->with('success', 'Signed in successfully. Welcome to Solqam!');

            } catch (Exception $e) {
                return redirect()->back()->withInput()->with('error', $e->getMessage());
            }
        }

        return view('auth/login', [
            'title'        => 'Sign In — Solqam Marketplace',
            'prefillLogin' => (string) (session()->get('pending_login_email') ?? ''),
        ]);
    }

    public function register()
    {
        if ($this->authService->isLoggedIn()) {
            return redirect()->to('/');
        }

        $defaultRole = $this->resolveRegisterRole();

        if ($this->request->is('post')) {
            $this->rememberRegisterDraft();
            if (! \App\Services\Auth\RateLimitService::hit('register', 5)) {
                return redirect()->to('/register')->withInput()->with('error', 'Too many registrations from this network. Wait 15 minutes.');
            }
            $role = $this->request->getPost('role') ?? 'customer';

            $rules = [
                'name'     => [
                    'rules'  => 'required|min_length[2]|max_length[150]',
                    'errors' => [
                        'required'   => 'Please enter your full name.',
                        'min_length' => 'Name must be at least 2 characters long.',
                    ],
                ],
                'email'    => [
                    'rules'  => 'required|valid_email|is_unique[users.email]',
                    'errors' => [
                        'required'    => 'Please enter your email address.',
                        'valid_email' => 'Please enter a valid email address (e.g. name@domain.com).',
                        'is_unique'   => 'This email address is already registered. Please choose another email or sign in.',
                    ],
                ],
                'phone'    => [
                    'rules'  => 'required|regex_match[/^[0-9]{10}$/]',
                    'errors' => [
                        'required'    => 'Please enter your mobile number.',
                        'regex_match' => 'Mobile number must be exactly 10 digits (numbers only, e.g. 3001234567).',
                    ],
                ],
                'password' => [
                    'rules'  => 'required|min_length[6]',
                    'errors' => [
                        'required'   => 'Please enter an account password.',
                        'min_length' => 'Password must be at least 6 characters long.',
                    ],
                ],
            ];

            if ($role === 'seller') {
                $rules['store_name'] = [
                    'rules'  => 'required|min_length[3]|max_length[150]',
                    'errors' => [
                        'required'   => 'Please enter your store or brand name.',
                        'min_length' => 'Store name must be at least 3 characters.',
                    ],
                ];
                $rules['city'] = [
                    'rules'  => 'required|min_length[2]|max_length[80]',
                    'errors' => [
                        'required'   => 'Please enter your operating city.',
                        'min_length' => 'City is required.',
                    ],
                ];
                $rules['cnic_or_ntn'] = [
                    'rules'  => 'required|regex_match[/^[0-9]{5}-[0-9]{7}-[0-9]$/]',
                    'errors' => [
                        'required'    => 'Please enter your CNIC.',
                        'regex_match' => 'CNIC must be 13 digits (e.g. 35201-1234567-1).',
                    ],
                ];
                $rules['business_address'] = [
                    'rules'  => 'required|min_length[8]|max_length[500]',
                    'errors' => [
                        'required'   => 'Please enter your business / warehouse address.',
                        'min_length' => 'Business address is too short.',
                    ],
                ];
                $rules['bank_name'] = [
                    'rules'  => 'required|min_length[2]|max_length[120]',
                    'errors' => [
                        'required' => 'Please enter your settlement bank name.',
                    ],
                ];
                $rules['account_number_or_iban'] = [
                    'rules'  => 'required|min_length[8]|max_length[34]',
                    'errors' => [
                        'required'   => 'Please enter your IBAN or account number.',
                        'min_length' => 'IBAN or account number is too short.',
                    ],
                ];
            }

            if (!$this->validate($rules)) {
                return redirect()->to('/register')->withInput()->with('errors', $this->validator->getErrors());
            }

            $post = $this->request->getPost();
            $tenDigits = preg_replace('/[^0-9]/', '', (string)($post['phone'] ?? ''));
            $normalizedPhone = (strlen($tenDigits) === 10) ? '0' . $tenDigits : $tenDigits;

            // Check if phone number already exists in database
            $existingUserWithPhone = $this->userModel->groupStart()
                ->where('phone', $normalizedPhone)
                ->orWhere('phone', $tenDigits)
                ->orWhere('phone', '+92' . $tenDigits)
                ->orWhere('phone', '92' . $tenDigits)
                ->groupEnd()
                ->first();

            if ($existingUserWithPhone) {
                return redirect()->to('/register')->withInput()->with('errors', [
                    'phone' => 'This mobile number is already registered. Please use another mobile number or sign in.'
                ]);
            }

            $post['phone'] = $normalizedPhone;

            try {
                if ($role === 'seller') {
                    $result = $this->authService->registerSeller($post, [
                        'store_name'             => $post['store_name'],
                        'business_name'          => $post['business_name'] ?? null,
                        'cnic_or_ntn'            => $post['cnic_or_ntn'] ?? null,
                        'business_address'       => $post['business_address'] ?? null,
                        'city'                   => $post['city'] ?? null,
                        'bank_account_title'     => $post['bank_account_title'] ?? null,
                        'bank_name'              => $post['bank_name'] ?? null,
                        'account_number_or_iban' => $post['account_number_or_iban'] ?? null,
                    ]);
                } else {
                    $result = $this->authService->registerCustomer($post);
                }

                session()->remove('register_draft');
                session()->set('pending_verify_phone', $result['phone']);
                session()->set('pending_login_email', trim((string) ($post['email'] ?? '')));

                $mailOk = \App\Services\Mail\MailService::$lastOk;
                $flash = $mailOk
                    ? 'Account created. We sent a verification code to your email. Check inbox and spam.'
                    : self::otpMailFailMessage((string) ($post['email'] ?? ''));

                return redirect()->to('/verify-otp')->with($mailOk ? 'success' : 'error', $flash);

            } catch (Exception $e) {
                return redirect()->to('/register')->withInput()->with('error', $e->getMessage());
            }
        }

        return view('auth/register', [
            'title'       => 'Create an Account — Solqam Marketplace',
            'defaultRole' => $this->resolveRegisterRole(),
        ]);
    }

    public function verifyOtp()
    {
        if ($this->request->getGet('phone')) {
            session()->set('pending_verify_phone', (string) $this->request->getGet('phone'));

            return redirect()->to('/verify-otp');
        }

        $phone = (string) (session()->get('pending_verify_phone') ?? '');

        if ($this->request->is('post')) {
            if ($phone === '') {
                return redirect()->to('/register')->with('error', 'Start from registration. Your session expired.');
            }
            $otp = $this->request->getPost('otp');

            if ($this->authService->verifySignupOtp($phone, $otp)) {
                $user = $this->userModel->where('phone', $phone)->first();
                session()->remove('pending_verify_phone');
                $email = trim((string) ($user['email'] ?? ''));
                if ($email !== '') {
                    session()->set('pending_login_email', $email);
                }

                $msg = ($user['role'] ?? '') === 'seller'
                    ? 'Email verified. Sign in with your email and password. Your store stays pending until Solqam approves it.'
                    : 'Email verified. Sign in with your email and the password you created.';

                return redirect()->to('/login')->with('success', $msg);
            }

            return redirect()->to('/verify-otp')->with('error', 'Invalid or expired verification code. Please try again.');
        }

        if ($phone === '') {
            return redirect()->to('/register')->with('error', 'Start from registration to verify your email.');
        }

        $user = $this->userModel->where('phone', $phone)->first();

        return view('auth/verify_otp', [
            'title' => 'Verify email — Solqam Marketplace',
            'email' => $user['email'] ?? '',
        ]);
    }

    public function resendSignupOtp()
    {
        $phone = (string) (session()->get('pending_verify_phone') ?? '');
        if ($phone === '') {
            return redirect()->to('/register')->with('error', 'Start from registration to verify your email.');
        }
        if (! \App\Services\Auth\RateLimitService::hit('otp_resend', 4)) {
            return redirect()->to('/verify-otp')->with('error', 'Too many resend attempts. Wait a few minutes.');
        }

        $this->authService->resendSignupOtp($phone);

        $mailOk = \App\Services\Mail\MailService::$lastOk;
        $user   = $this->userModel->where('phone', $phone)->first();

        return redirect()->to('/verify-otp')->with(
            $mailOk ? 'success' : 'error',
            $mailOk
                ? 'A new code was sent to your email. Check inbox and spam.'
                : self::otpMailFailMessage((string) ($user['email'] ?? ''))
        );
    }

    public function forgotPassword()
    {
        if ($this->authService->isLoggedIn()) {
            return redirect()->to('/');
        }

        if ($this->request->is('post')) {
            $login = trim((string) $this->request->getPost('login'));
            if ($login === '') {
                return redirect()->back()->with('error', 'Enter your email or mobile number.');
            }
            try {
                $result = $this->authService->startPasswordReset($login);
                session()->set('password_reset', [
                    'user_id' => $result['user_id'],
                    'phone'   => $result['phone'],
                    'verified'=> false,
                ]);
                return redirect()->to('/forgot-password/verify')
                    ->with('success', 'We sent a verification code to your registered email.');
            } catch (Exception $e) {
                return redirect()->back()->withInput()->with('error', $e->getMessage());
            }
        }

        return view('auth/forgot_password', [
            'title' => 'Forgot Password — Solqam Marketplace',
        ]);
    }

    public function forgotVerify()
    {
        $reset = session()->get('password_reset');
        if (!$reset) {
            return redirect()->to('/forgot-password')->with('error', 'Start password reset from this page.');
        }

        if ($this->request->is('post')) {
            $otp = (string) $this->request->getPost('otp');
            if ($this->authService->verifyResetOtp($reset['phone'], $otp)) {
                $reset['verified'] = true;
                session()->set('password_reset', $reset);
                return redirect()->to('/forgot-password/reset')->with('success', 'Code verified. Set a new password.');
            }
            return redirect()->back()->with('error', 'Invalid or expired code. Check your email, or resend the code.');
        }

        $user = $this->userModel->where('phone', $reset['phone'])->first();

        return view('auth/forgot_verify', [
            'title' => 'Verify reset code — Solqam Marketplace',
            'email' => $user['email'] ?? '',
        ]);
    }

    public function resendResetOtp()
    {
        $reset = session()->get('password_reset');
        if (!$reset) {
            return redirect()->to('/forgot-password')->with('error', 'Start password reset from this page.');
        }
        if (! \App\Services\Auth\RateLimitService::hit('otp_resend', 4)) {
            return redirect()->to('/forgot-password/verify')->with('error', 'Too many resend attempts. Wait a few minutes.');
        }
        $this->authService->resendPasswordResetOtp((string) $reset['phone']);

        return redirect()->to('/forgot-password/verify')
            ->with('success', 'A new code was sent to your email.');
    }

    public function forgotReset()
    {
        $reset = session()->get('password_reset');
        if (!$reset || empty($reset['verified'])) {
            return redirect()->to('/forgot-password')->with('error', 'Verify the email code first.');
        }

        if ($this->request->is('post')) {
            $password = (string) $this->request->getPost('password');
            $confirm  = (string) $this->request->getPost('password_confirm');
            if (strlen($password) < 6) {
                return redirect()->back()->with('error', 'Password must be at least 6 characters.');
            }
            if ($password !== $confirm) {
                return redirect()->back()->with('error', 'Passwords do not match.');
            }
            try {
                $this->authService->updatePassword((int) $reset['user_id'], $password);
                session()->remove('password_reset');
                return redirect()->to('/login')->with('success', 'Password updated. Sign in with your new password.');
            } catch (Exception $e) {
                return redirect()->back()->with('error', $e->getMessage());
            }
        }

        return view('auth/forgot_reset', [
            'title' => 'Set New Password — Solqam Marketplace',
        ]);
    }

    protected static function otpMailFailMessage(string $email): string
    {
        $email = strtolower(trim($email));
        $host  = strtolower((string) parse_url('http://' . $email, PHP_URL_HOST));
        if (str_contains($email, '@')) {
            $host = substr($email, strrpos($email, '@') + 1);
        }
        $debug = strtolower(\App\Services\Mail\MailService::$lastError);
        $sameDomain = $host === 'solqam.com' || str_ends_with($host, '.solqam.com');
        $unknownBox = str_contains($debug, 'user unknown')
            || str_contains($debug, 'no such user')
            || str_contains($debug, 'mailbox unavailable')
            || str_contains($debug, '550');

        if ($sameDomain || $unknownBox) {
            return 'Account created, but this email address has no mailbox (so the code could not be delivered). Use a real Gmail, Yahoo or Outlook address — then the code arrives. You can still sign in with your password.';
        }

        return 'Account created, but the verification email did not send. Use Resend code. Real Gmail/Yahoo inboxes receive OTP when SMTP is working. You can still sign in with your password.';
    }

    public function logout()
    {
        $this->authService->logout();
        return redirect()->to('/')->with('info', 'You have been logged out.');
    }

    protected function rememberRegisterDraft(): void
    {
        $post = $this->request->getPost() ?? [];
        unset($post['csrf_test_name'], $post[csrf_token()]);
        session()->set('register_draft', $post);
    }

    protected function resolveRegisterRole(): string
    {
        $role = (string) old('role', '');
        if ($role === '') {
            $draft = session()->get('register_draft') ?? [];
            $role  = (string) ($draft['role'] ?? '');
        }
        if ($role === '') {
            $role = (string) $this->request->getGet('role');
        }

        return $role === 'seller' ? 'seller' : 'customer';
    }
}
