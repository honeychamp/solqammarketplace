<?php

namespace App\Controllers\Api\V1;

use CodeIgniter\RESTful\ResourceController;

class BaseApiController extends ResourceController
{
    protected $format = 'json';

    protected function respondSuccess($data = null, string $message = 'Success', int $statusCode = 200)
    {
        return $this->response->setStatusCode($statusCode)->setJSON([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data,
        ]);
    }

    protected function respondFail(string $message = 'Error', $errors = null, int $statusCode = 400)
    {
        return $this->response->setStatusCode($statusCode)->setJSON([
            'status'  => 'error',
            'message' => $message,
            'errors'  => $errors,
        ]);
    }

    protected function getAuthenticatedUser(): ?array
    {
        $session = session();
        if ($session->has('api_user')) {
            return $session->get('api_user');
        }
        if ($session->has('user')) {
            return $session->get('user');
        }

        $authHeader = $this->request->getHeaderLine('Authorization');
        if (preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
            $userModel = new \App\Models\UserModel();
            return $userModel->where('api_token', $matches[1])->first();
        }

        return null;
    }
}
