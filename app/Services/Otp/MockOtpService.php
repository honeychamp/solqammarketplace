<?php

namespace App\Services\Otp;

use App\Models\OtpLogModel;

class MockOtpService implements OtpServiceInterface
{
    protected OtpLogModel $otpLogModel;

    public function __construct()
    {
        $this->otpLogModel = new OtpLogModel();
    }

    public function generateOtp(string $phone, string $purpose = 'signup', ?string $email = null): string
    {
        $code = (string) random_int(1000, 9999);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        $this->otpLogModel->insert([
            'phone'      => $phone,
            'otp_code'   => $code,
            'purpose'    => $purpose,
            'is_used'    => 0,
            'expires_at' => $expiresAt,
        ]);

        log_message('info', "[Solqam OTP] Mock SMS to {$phone}: Your verification code is {$code}");

        return $code;
    }

    public function verifyOtp(string $phone, string $code, string $purpose = 'signup'): bool
    {
        $log = $this->otpLogModel
            ->where('phone', $phone)
            ->where('otp_code', $code)
            ->where('purpose', $purpose)
            ->where('is_used', 0)
            ->where('expires_at >=', date('Y-m-d H:i:s'))
            ->orderBy('id', 'DESC')
            ->first();

        if ($log) {
            $this->otpLogModel->update($log['id'], ['is_used' => 1]);
            return true;
        }

        return false;
    }

    public function getLatestOtp(string $phone): ?string
    {
        $log = $this->otpLogModel
            ->where('phone', $phone)
            ->orderBy('id', 'DESC')
            ->first();

        return $log ? (string) $log['otp_code'] : null;
    }
}
