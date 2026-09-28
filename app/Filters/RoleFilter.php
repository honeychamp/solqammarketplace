<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $allowedRoles = $arguments ?? [];
        $isAdminRole = in_array('admin', $allowedRoles, true);

        if (!$session->get('user.isLoggedIn')) {
            if ($isAdminRole) {
                return redirect()->to('/admin/login')->with('error', 'Administrator login required.');
            }
            return redirect()->to('/login')->with('error', 'Please log in to continue.');
        }

        $userRole = $session->get('user.role');

        if (!empty($allowedRoles) && !in_array($userRole, $allowedRoles, true)) {
            if ($isAdminRole) {
                return redirect()->to('/admin/login')->with('error', 'Access Denied: Only platform administrators are permitted to access this console.');
            }
            // If admin or seller lands on wrong page, redirect to their home
            if ($userRole === 'admin') {
                return redirect()->to('/admin/dashboard')->with('error', 'Access denied.');
            }
            if ($userRole === 'seller') {
                return redirect()->to('/seller/dashboard')->with('error', 'Access denied.');
            }
            return redirect()->to('/')->with('error', 'You do not have permission to access that area.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No action needed
    }
}
