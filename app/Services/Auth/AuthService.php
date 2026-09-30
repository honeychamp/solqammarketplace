<?php

namespace App\Services\Auth;

use App\Models\SellerProfileModel;
use App\Models\UserModel;
use App\Services\Otp\OtpServiceInterface;
use App\Services\Wallet\WalletService;
use Config\Database;
use RuntimeException;

class AuthService
{
    protected UserModel $userModel;
    protected SellerProfileModel $sellerProfileModel;
    protected OtpServiceInterface $otpService;
    protected WalletService $walletService;
    protected $session;
    protected $db;

    public function __construct(?OtpServiceInterface $otpService = null)
    {
        $this->userModel          = new UserModel();
        $this->sellerProfileModel = new SellerProfileModel();
        $this->otpService         = $otpService ?? \App\Services\Otp\OtpServiceFactory::make();
        $this->walletService      = new WalletService();
        $this->session            = session();
        $this->db                 = Database::connect();
    }

    /**
     * Register a new Customer. Creates unverified customer and generates OTP.
     */
    public function registerCustomer(array $data): array
    {
        $this->db->transStart();

        $apiToken = bin2hex(random_bytes(32));

        $userId = $this->userModel->insert([
            'name'          => $data['name'],
            'email'         => $data['email'],
            'phone'         => $data['phone'],
            'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
            'role'          => 'customer',
            'status'        => 'active',
            'is_verified'   => 0, // Requires OTP verification
            'api_token'     => $apiToken,
        ]);

        // Auto-initialize Wallet ledger
        $this->walletService->getOrCreateWallet($userId);

        $this->db->transComplete();

        $otp = $this->otpService->generateOtp($data['phone'], 'signup', $data['email'] ?? null);

        return [
            'user_id' => $userId,
            'phone'   => $data['phone'],
            'otp'     => $otp,
        ];
    }

    /**
     * Register a new Seller. Creates unverified & pending seller and profile, and generates OTP.
     */
    public function registerSeller(array $userData, array $sellerData): array
    {
        $this->db->transStart();

        $apiToken = bin2hex(random_bytes(32));

        $userId = $this->userModel->insert([
            'name'          => $userData['name'],
            'email'         => $userData['email'],
            'phone'         => $userData['phone'],
            'password_hash' => password_hash($userData['password'], PASSWORD_BCRYPT),
            'role'          => 'seller',
            'status'        => 'active', // Can sign in; store stays pending until admin approval
            'is_verified'   => 0,
            'api_token'     => $apiToken,
        ]);

        $this->sellerProfileModel->insert([
            'user_id'                => $userId,
            'store_name'             => $sellerData['store_name'],
            'business_name'          => $sellerData['business_name'] ?? null,
            'cnic_or_ntn'            => $sellerData['cnic_or_ntn'] ?? null,
            'business_address'       => $sellerData['business_address'] ?? null,
            'city'                   => $sellerData['city'] ?? null,
            'bank_account_title'     => $sellerData['bank_account_title'] ?? null,
            'bank_name'              => $sellerData['bank_name'] ?? null,
            'account_number_or_iban' => $sellerData['account_number_or_iban'] ?? null,
            'approval_status'        => 'pending',
        ]);

        // Auto-initialize Wallet ledger
        $this->walletService->getOrCreateWallet($userId);

        $this->db->transComplete();

        $otp = $this->otpService->generateOtp($userData['phone'], 'signup', $userData['email'] ?? null);

        return [
            'user_id' => $userId,
            'phone'   => $userData['phone'],
            'otp'     => $otp,
        ];
    }

    /**
     * Verify Signup OTP and activate user's verification flag.
     */
    public function verifySignupOtp(string $phone, string $otp): bool
    {
        if (!$this->otpService->verifyOtp($phone, $otp, 'signup')) {
            return false;
        }

        $user = $this->userModel->where('phone', $phone)->first();
        if ($user) {
            $this->userModel->update($user['id'], ['is_verified' => 1]);
            return true;
        }

        return false;
    }

    public function resendSignupOtp(string $phone): string
    {
        $user = $this->userModel->where('phone', $phone)->first();
        $email = $user['email'] ?? null;

        return $this->otpService->generateOtp($phone, 'signup', $email);
    }

    public function resendPasswordResetOtp(string $phone): string
    {
        $user = $this->userModel->where('phone', $phone)->first();
        $email = $user['email'] ?? null;

        return $this->otpService->generateOtp($phone, 'password_reset', $email);
    }

    /**
     * Authenticate user for login.
     * Enforces role checks and seller approval status.
     */
    public function login(string $login, string $password): array
    {
        $user = $this->userModel->findByEmailOrPhone($login);
        if (!$user) {
            throw new RuntimeException("Invalid credentials.");
        }

        if (!password_verify($password, $user['password_hash'])) {
            throw new RuntimeException("Invalid credentials.");
        }

        if ($user['status'] === 'suspended') {
            throw new RuntimeException("Your account has been suspended by the administrator.");
        }

        // Sellers can log in freely — no approval gate

        // Set session
        $this->setSession($user);

        if (in_array($user['role'], ['customer', 'seller'], true)) {
            try {
                \App\Services\Mail\MailService::sendLoginUsername($user);
            } catch (\Throwable $e) {
                log_message('error', 'Login email failed: ' . $e->getMessage());
            }
        }

        return $user;
    }

    public function setSession(array $user): void
    {
        $userData = [
            'id'          => (int) $user['id'],
            'name'        => $user['name'],
            'email'       => $user['email'],
            'phone'       => $user['phone'],
            'role'        => $user['role'],
            'status'      => $user['status'],
            'is_verified' => (int) $user['is_verified'],
            'api_token'   => $user['api_token'],
            'isLoggedIn'  => true,
        ];

        if ($user['role'] === 'seller') {
            $profile = $this->sellerProfileModel->getByUserId((int) $user['id']);
            $userData['store_name']       = $profile['store_name'] ?? 'Vendor Store';
            $userData['approval_status']  = $profile['approval_status'] ?? 'pending';
        }

        $this->session->set('user', $userData);
    }

    public function getCurrentUser(): ?array
    {
        return $this->session->get('user');
    }

    public function isLoggedIn(): bool
    {
        if (!$this->session->get('user.isLoggedIn')) {
            return false;
        }

        $userId = (int) $this->session->get('user.id');
        if ($userId <= 0) {
            $this->logout();
            return false;
        }

        $dbUser = $this->userModel->find($userId);
        if (!$dbUser) {
            $this->logout();
            return false;
        }

        return true;
    }

    public function hasRole(string $role): bool
    {
        return $this->session->get('user.role') === $role;
    }

    public function logout(): void
    {
        $this->session->remove('user');
        $this->session->destroy();
    }

    public function startPasswordReset(string $login): array
    {
        $user = $this->userModel->findByEmailOrPhone($login);
        if (!$user) {
            throw new RuntimeException('No account found for that email or mobile number.');
        }
        if ($user['role'] === 'admin') {
            throw new RuntimeException('Administrator passwords cannot be reset here. Use the admin login portal.');
        }
        if ($user['status'] === 'suspended') {
            throw new RuntimeException('This account is suspended. Contact Solqam support.');
        }

        $otp = $this->otpService->generateOtp($user['phone'], 'password_reset', $user['email'] ?? null);

        return [
            'user_id' => (int) $user['id'],
            'phone'   => $user['phone'],
            'otp'     => $otp,
        ];
    }

    public function verifyResetOtp(string $phone, string $otp): bool
    {
        return $this->otpService->verifyOtp($phone, $otp, 'password_reset');
    }

    public function updatePassword(int $userId, string $password): void
    {
        $user = $this->userModel->find($userId);
        if (!$user || $user['role'] === 'admin') {
            throw new RuntimeException('Unable to update this account.');
        }
        $this->userModel->skipValidation(true)->update($userId, [
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);
    }
}
