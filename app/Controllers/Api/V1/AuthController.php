<?php

namespace App\Controllers\Api\V1;

use App\Services\Auth\AuthService;
use Exception;

class AuthController extends BaseApiController
{
    protected AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function register()
    {
        $rules = [
            'name'     => 'required|min_length[2]|max_length[150]',
            'email'    => 'required|valid_email|is_unique[users.email]',
            'phone'    => 'required|min_length[10]|max_length[20]|is_unique[users.phone]',
            'password' => 'required|min_length[6]',
            'role'     => 'permit_empty|in_list[customer,seller]',
        ];

        if (!$this->validate($rules)) {
            return $this->respondFail('Validation failed', $this->validator->getErrors(), 422);
        }

        $input = $this->request->getJSON(true) ?? $this->request->getPost();
        $role = $input['role'] ?? 'customer';

        try {
            if ($role === 'seller') {
                $sellerRules = [
                    'store_name' => 'required|min_length[3]|max_length[150]',
                ];
                if (!$this->validate($sellerRules)) {
                    return $this->respondFail('Seller details validation failed', $this->validator->getErrors(), 422);
                }

                $result = $this->authService->registerSeller($input, [
                    'store_name'             => $input['store_name'],
                    'business_name'          => $input['business_name'] ?? null,
                    'cnic_or_ntn'            => $input['cnic_or_ntn'] ?? null,
                    'business_address'       => $input['business_address'] ?? null,
                    'city'                   => $input['city'] ?? null,
                    'bank_account_title'     => $input['bank_account_title'] ?? null,
                    'bank_name'              => $input['bank_name'] ?? null,
                    'account_number_or_iban' => $input['account_number_or_iban'] ?? null,
                ]);

                $payload = [
                    'user_id' => $result['user_id'],
                    'phone'   => $result['phone'],
                ];

                return $this->respondSuccess($payload, 'Seller registered. Verify the OTP sent to your email.', 201);
            }

            $result = $this->authService->registerCustomer($input);
            $payload = [
                'user_id' => $result['user_id'],
                'phone'   => $result['phone'],
            ];

            return $this->respondSuccess($payload, 'Customer registered. Verify the OTP sent to your email.', 201);

        } catch (Exception $e) {
            return $this->respondFail($e->getMessage(), null, 500);
        }
    }

    public function verifyOtp()
    {
        $input = $this->request->getJSON(true) ?? $this->request->getPost();
        $phone = $input['phone'] ?? null;
        $otp   = $input['otp'] ?? null;

        if (!$phone || !$otp) {
            return $this->respondFail('Phone and OTP code are required.', null, 422);
        }

        if ($this->authService->verifySignupOtp($phone, $otp)) {
            return $this->respondSuccess(null, 'Phone verified successfully.');
        }

        return $this->respondFail('Invalid or expired OTP code.', null, 400);
    }

    public function login()
    {
        $input = $this->request->getJSON(true) ?? $this->request->getPost();
        $login    = $input['login'] ?? null;
        $password = $input['password'] ?? null;

        if (!$login || !$password) {
            return $this->respondFail('Login (email or phone) and password are required.', null, 422);
        }

        try {
            $user = $this->authService->login($login, $password);

            return $this->respondSuccess([
                'user' => [
                    'id'         => (int) $user['id'],
                    'name'       => $user['name'],
                    'email'      => $user['email'],
                    'phone'      => $user['phone'],
                    'role'       => $user['role'],
                    'status'     => $user['status'],
                    'api_token'  => $user['api_token'],
                ],
            ], 'Login successful.');

        } catch (Exception $e) {
            return $this->respondFail($e->getMessage(), null, 401);
        }
    }
}
