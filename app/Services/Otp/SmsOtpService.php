<?php

namespace App\Services\Otp;

use App\Models\OtpLogModel;
use App\Services\Sms\SmsNotifier;

class SmsOtpService implements OtpServiceInterface
{
    protected OtpLogModel $otpLogModel;

    public function __construct()
    {
        $this->otpLogModel = new OtpLogModel();
    }

    public function generateOtp(string $phone, string $purpose = 'signup', ?string $email = null): string
    {
        $code = (string) random_int(100000, 999999);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        $this->otpLogModel->insert([
            'phone'      => $phone,
            'otp_code'   => $code,
            'purpose'    => $purpose,
            'is_used'    => 0,
            'expires_at' => $expiresAt,
        ]);

        try {
            $to = trim((string) $email);
            if ($to === '') {
                $user = (new \App\Models\UserModel())->where('phone', $phone)->first();
                $to = (string) ($user['email'] ?? '');
            }
            if ($to !== '') {
                $sent = \App\Services\Mail\MailService::sendOtp($to, $code, $purpose);
                if (! $sent) {
                    log_message('error', 'OTP email was not delivered to ' . $to);
                }
            } else {
                log_message('error', 'OTP email skipped: no email for phone ' . $phone);
            }
        } catch (\Throwable $e) {
            log_message('error', 'OTP email failed: ' . $e->getMessage());
        }

        try {
            SmsNotifier::otp($phone, $code, $purpose);
        } catch (\Throwable $e) {
            log_message('error', 'OTP SMS failed: ' . $e->getMessage());
        }

        return $code;
    }

    public function verifyOtp(string $phone, string $code, string $purpose = 'signup'): bool
    {
        $code = trim($code);

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
        if (! sms_demo_mode()) {
            return null;
        }
        $log = $this->otpLogModel
            ->where('phone', $phone)
            ->orderBy('id', 'DESC')
            ->first();

        return $log ? (string) $log['otp_code'] : null;
    }
}
