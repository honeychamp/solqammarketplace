<?php

namespace App\Commands;

use App\Services\Mail\MailService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class MailOtpTest extends BaseCommand
{
    protected $group       = 'Solqam';
    protected $name        = 'mail:otp-test';
    protected $description = 'Send a test verification email using current SMTP settings.';
    protected $usage       = 'mail:otp-test <email>';

    public function run(array $params)
    {
        $to = trim((string) ($params[0] ?? ''));
        if ($to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            CLI::error('Usage: php spark mail:otp-test you@email.com');

            return EXIT_ERROR;
        }

        $ok = MailService::sendOtp($to, '847291', 'signup');
        CLI::write($ok ? 'SENT' : 'FAIL');
        $status = WRITEPATH . 'mailbox/last_status.txt';
        if (is_file($status)) {
            CLI::write(file_get_contents($status));
        }

        return $ok ? EXIT_SUCCESS : EXIT_ERROR;
    }
}
