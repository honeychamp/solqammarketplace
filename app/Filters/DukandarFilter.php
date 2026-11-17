<?php

namespace App\Filters;

use App\Models\DukandarProfileModel;
use App\Services\Platform\SchemaHeal;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class DukandarFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        if (! $session->get('user.isLoggedIn')) {
            $session->set('redirect_url', current_url());
            $session->set('login_intent', 'merchant');

            return redirect()->to('/login')->with('error', 'Sign in to open Merchant Hub.');
        }
        $role = (string) $session->get('user.role');
        if ($role === 'admin') {
            return redirect()->to('/admin/dashboard');
        }
        try {
            SchemaHeal::ensureCardNetwork();
            $profile = (new DukandarProfileModel())->getByUserId((int) $session->get('user.id'));
        } catch (\Throwable $e) {
            $profile = null;
        }
        if (! $profile) {
            return redirect()->to(merchant_url('join'))->with('error', 'Create a merchant shop profile first.');
        }
        $session->set('user.dukandar_status', $profile['approval_status'] ?? 'pending');
        $session->set('user.shop_name', $profile['shop_name'] ?? 'Shop');
        $session->set('user.merchant_code', $profile['merchant_code'] ?? '');

        $status = (string) ($profile['approval_status'] ?? 'pending');
        $key    = merchant_hub_key();
        $method = strtoupper($request->getMethod());
        $open   = $key === 'dashboard'
            || $key === 'profile'
            || str_starts_with($key, 'profile')
            || $key === 'become-seller';
        if ($status === 'approved') {
            return;
        }
        if ($open && $method === 'GET') {
            return;
        }
        if ($key === 'profile' && $method === 'POST') {
            return;
        }
        if ($key === 'become-seller' && $method === 'POST') {
            return;
        }

        $msg = $status === 'rejected'
            ? 'This shop was not approved. Update your profile and wait for Solqam review.'
            : 'Your merchant account is pending admin approval. You can open the dashboard, but you cannot scan cards until approved.';

        return redirect()->to(merchant_url('dashboard'))->with('error', $msg);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
