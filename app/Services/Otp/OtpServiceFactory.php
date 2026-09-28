<?php

namespace App\Services\Otp;

class OtpServiceFactory
{
    public static function make(): OtpServiceInterface
    {
        return new SmsOtpService();
    }
}
