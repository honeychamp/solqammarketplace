<?php

namespace App\Filters;

use App\Models\SellerProfileModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class SellerApprovalFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $userId  = (int) $session->get('user.id');
        if ($userId <= 0) {
            return;
        }

        $profile = (new SellerProfileModel())->getByUserId($userId);
        $status  = (string) ($profile['approval_status'] ?? 'pending');
        $session->set('user.approval_status', $status);

        if ($status === 'approved') {
            return;
        }

        $uri    = trim((string) uri_string(), '/');
        $method = strtoupper($request->getMethod());
        $isDashboard = $uri === 'seller/dashboard' || str_ends_with($uri, '/seller/dashboard');

        if ($isDashboard && $method === 'GET') {
            return;
        }

        $message = $status === 'rejected'
            ? 'This seller account was not approved. Contact Solqam support.'
            : 'Your seller account is pending admin approval. You can open the dashboard, but you cannot manage products, orders, or payouts until you are approved.';

        return redirect()->to('/seller/dashboard')->with('error', $message);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
