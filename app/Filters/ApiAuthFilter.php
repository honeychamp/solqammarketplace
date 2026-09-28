<?php

namespace App\Filters;

use App\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ApiAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $authHeader = $request->getHeaderLine('Authorization');
        $token = null;

        if (preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
        } elseif ($request->getHeaderLine('X-API-KEY')) {
            $token = $request->getHeaderLine('X-API-KEY');
        }

        // Also fallback to current session if requested via web-browser fetch
        $session = session();
        if (!$token && $session->get('user.isLoggedIn')) {
            return;
        }

        if (!$token) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Unauthorized: Missing or invalid API authentication token.',
                ]);
        }

        $userModel = new UserModel();
        $user = $userModel->where('api_token', $token)->first();

        if (!$user) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Unauthorized: Invalid token.',
                ]);
        }

        // Inject authenticated user into session or request state
        $session->set('api_user', $user);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No action needed
    }
}
