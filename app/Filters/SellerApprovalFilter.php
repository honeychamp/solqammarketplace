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
        $userId = $session->get('user.id');
        $userRole = $session->get('user.role');

        // Approval requirement removed — all registered sellers have direct access to their store
        return;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No action needed
    }
}
