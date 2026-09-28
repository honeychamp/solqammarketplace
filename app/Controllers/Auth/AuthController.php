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
                    return redirect()->to('/seller/dashboard')->with('success', 'Welcome to your Seller Dashboard!');
                }

                $redirectUrl = session()->get('redirect_url') ?? '/';
                session()->remove('redirect_url');
                return redirect()->to($redirectUrl)->with('success', 'Signed in successfully. Welcome to Solqam!');

            } catch (Exception $e) {
                return redirect()->back()->withInput()->with('error', $e->getMessage());
            }
        }

        return view('auth/login', [
            'title' => 'Sign In — Solqam Market Place',
        ]);
    }

    public function register()
    {
        if ($this->authService->isLoggedIn()) {
            return redirect()->to('/');
        }

        $defaultRole = $this->request->getGet('role') === 'seller' ? 'seller' : 'customer';

        if ($this->request->is('post')) {
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
                    'rules'  => 'required',
                    'errors' => [
                        'required' => 'Please enter your operating city.',
                    ],
                ];
            }

            if (!$this->validate($rules)) {
                return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
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
                return redirect()->back()->withInput()->with('errors', [
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

                session()->setFlashdata('registered_phone', $result['phone']);

                return redirect()->to('/verify-otp?phone=' . urlencode($result['phone']))
                    ->with('success', 'Account created. OTP aapki email par bhej diya gaya hai.');

            } catch (Exception $e) {
                return redirect()->back()->withInput()->with('error', $e->getMessage());
            }
        }

        return view('auth/register', [
            'title'       => 'Create an Account — Solqam Market Place',
            'defaultRole' => $defaultRole,
        ]);
    }

    public function verifyOtp()
    {
        $phone = $this->request->getGet('phone') ?? session()->getFlashdata('registered_phone');

        if ($this->request->is('post')) {
            $phone = $this->request->getPost('phone');
            $otp   = $this->request->getPost('otp');

            if ($this->authService->verifySignupOtp($phone, $otp)) {
                $user = $this->userModel->where('phone', $phone)->first();
                $this->authService->setSession($user);

                if ($user['role'] === 'seller') {
                    return redirect()->to('/seller/dashboard')->with('success', 'Email verified. Welcome to your Seller Dashboard.');
                }

                return redirect()->to('/')->with('success', 'Email verified. Welcome to Solqam Market Place.');
            }

            return redirect()->back()->with('error', 'Invalid or expired verification code. Please try again.');
        }

        if ($this->request->getGet('resend') && $phone) {
            $this->authService->resendSignupOtp((string) $phone);
            return redirect()->to('/verify-otp?phone=' . urlencode((string) $phone))
                ->with('success', 'Naya OTP aapki email par bhej diya gaya hai.');
        }

        $user = $phone ? $this->userModel->where('phone', $phone)->first() : null;

        return view('auth/verify_otp', [
            'title' => 'Verify email OTP — Solqam Market Place',
            'phone' => $phone,
            'email' => $user['email'] ?? '',
        ]);
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
                    ->with('success', 'OTP aapki registered email par bhej diya gaya hai.');
            } catch (Exception $e) {
                return redirect()->back()->withInput()->with('error', $e->getMessage());
            }
        }

        return view('auth/forgot_password', [
            'title' => 'Forgot Password — Solqam Market Place',
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
            return redirect()->back()->with('error', 'Invalid or expired code. Check email, or resend OTP.');
        }

        if ($this->request->getGet('resend')) {
            $this->authService->resendPasswordResetOtp((string) $reset['phone']);
            return redirect()->to('/forgot-password/verify')
                ->with('success', 'Naya OTP aapki email par bhej diya gaya hai.');
        }

        $user = $this->userModel->where('phone', $reset['phone'])->first();

        return view('auth/forgot_verify', [
            'title' => 'Verify Reset Code — Solqam Market Place',
            'phone' => $reset['phone'],
            'email' => $user['email'] ?? '',
        ]);
    }

    public function forgotReset()
    {
        $reset = session()->get('password_reset');
        if (!$reset || empty($reset['verified'])) {
            return redirect()->to('/forgot-password')->with('error', 'Verify the email OTP first.');
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
            'title' => 'Set New Password — Solqam Market Place',
        ]);
    }

    public function logout()
    {
        $this->authService->logout();
        return redirect()->to('/')->with('info', 'You have been logged out.');
    }
}
