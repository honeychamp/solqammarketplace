<?php

namespace App\Services\Otp;

interface OtpServiceInterface
{
    /**
     * Generate and dispatch/log an OTP code for a phone number.
     */
    public function generateOtp(string $phone, string $purpose = 'signup', ?string $email = null): string;

    /**
     * Verify whether an OTP code is valid and unused.
     */
    public function verifyOtp(string $phone, string $code, string $purpose = 'signup'): bool;

    /**
     * Retrieve the latest generated OTP for a phone number (used in demo/mock mode).
     */
    public function getLatestOtp(string $phone): ?string;
}
