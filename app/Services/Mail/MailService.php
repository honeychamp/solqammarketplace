<?php

namespace App\Services\Mail;

class MailService
{
    /**
     * Send HTML mail. If SMTP is not set, save a copy under writable/mailbox.
     */
    public static function send(string $to, string $subject, string $html): bool
    {
        $to = trim($to);
        if ($to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        self::archive($to, $subject, $html);

        try {
            $fromEmail = (string) env('email.fromEmail', 'info@solqamtech.com');
            $fromName  = (string) env('email.fromName', 'SolqamTech');
            $smtpHost  = (string) env('email.SMTPHost', '');

            $email = \Config\Services::email();
            if ($smtpHost !== '') {
                $email->initialize([
                    'protocol'    => 'smtp',
                    'SMTPHost'    => $smtpHost,
                    'SMTPUser'    => (string) env('email.SMTPUser', ''),
                    'SMTPPass'    => (string) env('email.SMTPPass', ''),
                    'SMTPPort'    => (int) env('email.SMTPPort', 587),
                    'SMTPCrypto'  => (string) env('email.SMTPCrypto', 'tls'),
                    'SMTPTimeout' => 20,
                    'mailType'    => 'html',
                    'charset'     => 'UTF-8',
                    'fromEmail'   => $fromEmail,
                    'fromName'    => $fromName,
                ]);
            }

            $email->setFrom($fromEmail, $fromName);
            $email->setTo($to);
            $email->setSubject($subject);
            $email->setMailType('html');
            $email->setMessage($html);

            $ok = $email->send(false);
            if (! $ok) {
                log_message('info', 'Email send skipped/failed: ' . $email->printDebugger(['headers']));
            }

            return $ok;
        } catch (\Throwable $e) {
            log_message('error', 'Email error: ' . $e->getMessage());

            return false;
        }
    }

    public static function sendLoginUsername(array $user): void
    {
        $email = (string) ($user['email'] ?? '');
        $name  = (string) ($user['name'] ?? 'Member');
        $role  = (string) ($user['role'] ?? 'customer');
        $username = $email;
        $phone = (string) ($user['phone'] ?? '');
        $html = '<p>Assalam o Alaikum ' . htmlspecialchars($name) . ',</p>'
            . '<p>Aap Solqam Market Place par <strong>' . htmlspecialchars($role) . '</strong> ke taur par login ho chuke hain.</p>'
            . '<p>Aapka username (login email): <strong>' . htmlspecialchars($username) . '</strong></p>'
            . ($phone !== '' ? '<p>Registered mobile: ' . htmlspecialchars($phone) . '</p>' : '')
            . '<p>Agar yeh login aapne nahi kiya to password change karein.</p>'
            . '<p>— Solqam Market Place</p>';

        self::send($email, 'Solqam login — aapka username', $html);
    }

    public static function sendOtp(string $email, string $code, string $purpose = 'signup'): void
    {
        $label = $purpose === 'password_reset' ? 'password reset' : 'account verify';
        $html = '<p>Aapka Solqam ' . htmlspecialchars($label) . ' code:</p>'
            . '<p style="font-size:28px;letter-spacing:6px;font-weight:700;">' . htmlspecialchars($code) . '</p>'
            . '<p>10 minutes valid. Kisi se share na karein.</p>';

        self::send($email, 'Solqam verification code', $html);
    }

    protected static function archive(string $to, string $subject, string $html): void
    {
        $dir = WRITEPATH . 'mailbox';
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $file = $dir . DIRECTORY_SEPARATOR . date('Ymd_His') . '_' . preg_replace('/[^a-z0-9]+/i', '_', $to) . '.html';
        $body = '<h3>' . htmlspecialchars($subject) . '</h3><p>To: ' . htmlspecialchars($to) . '</p>' . $html;
        @file_put_contents($file, $body);
    }
}
