<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $uri = uri_string();
        $isAdminRoute = str_starts_with($uri, 'admin') && !str_starts_with($uri, 'admin/login');

        if (!$session->get('user.isLoggedIn')) {
            $session->set('redirect_url', current_url());
            if ($isAdminRoute) {
                return redirect()->to('/admin/login')->with('error', 'Administrator authentication required.');
            }
            return redirect()->to('/login')->with('error', 'Please log in to continue.');
        }

        $userId = (int) $session->get('user.id');
        $userModel = new \App\Models\UserModel();
        if ($userId <= 0 || !$userModel->find($userId)) {
            $session->remove('user');
            $session->destroy();
            if ($isAdminRoute) {
                return redirect()->to('/admin/login')->with('error', 'Admin session expired. Please log in again.');
            }
            return redirect()->to('/login')->with('error', 'Session expired. Please log in again.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No action needed
    }
}
