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
     * /admin → login (or dashboard if already signed in as admin)
     */
    public function entry()
    {
        if ($this->authService->isLoggedIn() && session()->get('user.role') === 'admin') {
            return redirect()->to('/admin/dashboard');
        }

        return redirect()->to('/admin/login');
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

                $this->authService->setSession($user);
                return redirect()->to('/admin/dashboard')->with('success', 'Welcome to Solqam Admin.');

            } catch (\Throwable $e) {
                log_message('error', 'Admin login: ' . $e->getMessage());

                return redirect()->back()->withInput()->with('error', 'Could not sign in. Confirm an admin row exists in the users table (phpMyAdmin).');
            }
        }

        return view('admin/auth/login', [
            'title' => 'Admin Console Login — Solqam Marketplace',
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
