<?php

namespace App\Controllers\Admin;

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

    /**
     * Dedicated Administrator Login Portal
     */
    public function login()
    {
        // If already logged in as Admin, redirect directly to dashboard
        if ($this->authService->isLoggedIn()) {
            if (session()->get('user.role') === 'admin') {
                return redirect()->to('/admin/dashboard');
            }
            // If logged in as customer/seller, logout to allow admin sign in
            $this->authService->logout();
        }

        if ($this->request->is('post')) {
            if (! \App\Services\Auth\RateLimitService::hit('admin_login', 8)) {
                return redirect()->back()->withInput()->with('error', 'Too many admin login attempts.');
            }
            $rules = [
                'login'    => 'required',
                'password' => 'required',
            ];

            if (!$this->validate($rules)) {
                return redirect()->back()->withInput()->with('error', 'Please provide administrator email/username and password.');
            }

            $login    = trim((string) $this->request->getPost('login'));
            $password = (string) $this->request->getPost('password');

            try {
                $user = $this->userModel->findByEmailOrPhone($login);
                if (!$user || !password_verify($password, $user['password_hash'])) {
                    return redirect()->back()->withInput()->with('error', 'Invalid administrator credentials.');
                }

                // Strict check: Only Admin role is allowed here
                if ($user['role'] !== 'admin') {
                    return redirect()->back()->withInput()->with('error', 'Access Denied: This portal is strictly restricted to Solqam Platform Administrators.');
                }

                if ($user['status'] === 'suspended') {
                    return redirect()->back()->withInput()->with('error', 'Administrator account suspended. Contact system root.');
                }

                $code = (string) random_int(100000, 999999);
                session()->set('admin_2fa', [
                    'user_id' => (int) $user['id'],
                    'code'    => $code,
                    'until'   => time() + 600,
                ]);
                \App\Services\Mail\MailService::sendAdmin2fa((string) $user['email'], $code);

                return redirect()->to('/admin/verify-2fa')->with('success', 'Verification code aapki admin email par bhej diya gaya.');

            } catch (Exception $e) {
                return redirect()->back()->withInput()->with('error', $e->getMessage());
            }
        }

        return view('admin/auth/login', [
            'title' => 'Admin Console Login — Solqam Marketplace',
        ]);
    }

    public function verify2fa()
    {
        $pending = session()->get('admin_2fa');
        if (! is_array($pending) || empty($pending['user_id'])) {
            return redirect()->to('/admin/login')->with('error', 'Login first.');
        }

        if ($this->request->is('post')) {
            $code = trim((string) $this->request->getPost('otp'));
            if (time() > (int) ($pending['until'] ?? 0) || $code !== (string) $pending['code']) {
                return redirect()->back()->with('error', 'Invalid or expired admin code.');
            }
            $user = $this->userModel->find((int) $pending['user_id']);
            session()->remove('admin_2fa');
            if (! $user) {
                return redirect()->to('/admin/login')->with('error', 'Admin not found.');
            }
            $this->authService->setSession($user);
            return redirect()->to('/admin/dashboard')->with('success', 'Authenticated. Welcome to Solqam Admin.');
        }

        return view('admin/auth/verify_2fa', [
            'title' => 'Admin verification — Solqam',
        ]);
    }

    /**
     * Dedicated Administrator Logout
     */
    public function logout()
    {
        $this->authService->logout();
        return redirect()->to('/admin/login')->with('info', 'Administrator signed out safely.');
    }
}
