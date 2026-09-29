<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Services\Config\DotEnvWriter;

class AccountController extends BaseController
{
    public function index()
    {
        $user = (new UserModel())->find((int) session()->get('user.id'));
        if (! $user) {
            return redirect()->to('/admin/login');
        }

        if ($this->request->is('post')) {
            $email = trim((string) $this->request->getPost('email'));
            $name  = trim((string) $this->request->getPost('name'));
            $phone = trim((string) $this->request->getPost('phone'));
            $pass  = (string) $this->request->getPost('password');
            $confirm = (string) $this->request->getPost('password_confirm');

            if ($name === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return redirect()->back()->withInput()->with('error', 'Valid name and email required.');
            }

            $taken = (new UserModel())
                ->where('email', $email)
                ->where('id !=', (int) $user['id'])
                ->first();
            if ($taken) {
                return redirect()->back()->withInput()->with('error', 'That email is already used.');
            }

            $update = [
                'name'  => $name,
                'email' => $email,
                'phone' => $phone,
            ];
            if ($pass !== '') {
                if (strlen($pass) < 6) {
                    return redirect()->back()->withInput()->with('error', 'Password must be at least 6 characters.');
                }
                if ($pass !== $confirm) {
                    return redirect()->back()->withInput()->with('error', 'Passwords do not match.');
                }
                $update['password_hash'] = password_hash($pass, PASSWORD_DEFAULT);
            }

            (new UserModel())->update((int) $user['id'], $update);

            $env = [
                'admin.email' => $email,
                'admin.name'  => $name,
                'admin.phone' => $phone,
            ];
            if ($pass !== '') {
                $env['admin.password'] = $pass;
            }
            DotEnvWriter::upsert($env);

            $fresh = (new UserModel())->find((int) $user['id']);
            (new \App\Services\Auth\AuthService())->setSession($fresh);

            return redirect()->to('/admin/account')->with('success', 'Admin login saved. Database refresh will restore this email/password from .env.');
        }

        return view('admin/account/index', [
            'title' => 'Admin login — Solqam Admin',
            'user'  => $user,
        ]);
    }
}
