<?php

namespace App\Services\Mail;

class MailService
{
    public static bool $lastOk = false;
    public static string $lastError = '';

    /**
     * Send HTML mail. A copy is always saved under writable/mailbox.
     */
    public static function send(string $to, string $subject, string $html): bool
    {
        self::$lastOk = false;
        self::$lastError = '';

        $to = trim($to);
        if ($to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            self::$lastError = 'Invalid recipient email.';
            log_message('error', 'Email skipped: ' . self::$lastError);

            return false;
        }

        self::archive($to, $subject, $html);

        $fromEmail = (string) env('email.fromEmail', 'info@solqam.com');
        $fromName  = (string) env('email.fromName', 'Solqam Market Place');
        $smtpHost  = (string) env('email.SMTPHost', 'mail.solqam.com');
        $smtpUser  = (string) env('email.SMTPUser', '');
        $smtpPass  = (string) env('email.SMTPPass', '');
        $smtpPort  = (int) env('email.SMTPPort', 465);
        $smtpCrypto = strtolower((string) env('email.SMTPCrypto', 'ssl'));
        $protocol  = strtolower((string) env('email.protocol', $smtpHost !== '' ? 'smtp' : 'mail'));

        $smtpHost = trim($smtpHost);
        $smtpHost = (string) preg_replace('#^(ssl|tls|smtp|https?)://#i', '', $smtpHost);
        if (str_contains($smtpHost, ':') && ! str_contains($smtpHost, ']')) {
            $parts = explode(':', $smtpHost);
            $smtpHost = $parts[0];
            if ($smtpPort <= 0 && isset($parts[1]) && ctype_digit($parts[1])) {
                $smtpPort = (int) $parts[1];
            }
        }

        if ($fromEmail === '' && filter_var($smtpUser, FILTER_VALIDATE_EMAIL)) {
            $fromEmail = $smtpUser;
        }
        if ($fromEmail === '') {
            $fromEmail = 'info@solqam.com';
        }
        if ($smtpUser === '' && filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            $smtpUser = $fromEmail;
        }

        if ($smtpPort === 465) {
            $smtpCrypto = 'ssl';
        } elseif ($smtpPort === 587 && $smtpCrypto !== 'ssl') {
            $smtpCrypto = 'tls';
        }

        try {
            $attempts = [
                [
                    'port'   => $smtpPort > 0 ? $smtpPort : 465,
                    'crypto' => $smtpCrypto,
                ],
            ];
            $firstPort = $attempts[0]['port'];
            if ($firstPort === 465) {
                $attempts[] = ['port' => 587, 'crypto' => 'tls'];
            } elseif ($firstPort === 587) {
                $attempts[] = ['port' => 465, 'crypto' => 'ssl'];
            }

            $ok = false;
            $debug = '';
            foreach ($attempts as $i => $attempt) {
                $config = [
                    'protocol'    => ($protocol === 'smtp' && $smtpHost !== '') ? 'smtp' : 'mail',
                    'SMTPHost'    => $smtpHost,
                    'SMTPUser'    => $smtpUser,
                    'SMTPPass'    => $smtpPass,
                    'SMTPPort'    => $attempt['port'],
                    'SMTPCrypto'  => $attempt['crypto'],
                    'SMTPTimeout' => 25,
                    'mailType'    => 'html',
                    'charset'     => 'UTF-8',
                    'wordWrap'    => true,
                    'newline'     => "\r\n",
                    'CRLF'        => "\r\n",
                    'fromEmail'   => $fromEmail,
                    'fromName'    => $fromName,
                ];

                $email = new \CodeIgniter\Email\Email($config);
                $email->setFrom($fromEmail, $fromName);
                $email->setReplyTo($fromEmail, $fromName);
                $email->setTo($to);
                $email->setSubject($subject);
                $email->setMailType('html');
                $email->setMessage($html);

                $ok = $email->send(false);
                if ($ok) {
                    self::$lastOk = true;
                    self::writeStatus('SENT to ' . $to . ' via port ' . $attempt['port']);
                    break;
                }
                $debug = $email->printDebugger([]);
                $debug = preg_replace('/password[^\r\n]*/i', '[redacted]', (string) $debug) ?? $debug;
                self::$lastError = trim(strip_tags($debug));
                log_message('error', 'Email attempt ' . ($i + 1) . ' failed to ' . $to . ' port ' . $attempt['port'] . ': ' . self::$lastError);
            }

            if (! $ok) {
                self::writeStatus('FAIL to ' . $to . "\n" . self::$lastError);
            }

            return $ok;
        } catch (\Throwable $e) {
            self::$lastError = $e->getMessage();
            log_message('error', 'Email error: ' . $e->getMessage());
            self::writeStatus('ERROR ' . $e->getMessage());

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
        $html = self::wrap(
            'Sign-in notice',
            '<p>Hello ' . htmlspecialchars($name) . ',</p>'
            . '<p>You signed in to Solqam Market Place as <strong>' . htmlspecialchars($role) . '</strong>.</p>'
            . '<p>Username (login email): <strong>' . htmlspecialchars($username) . '</strong></p>'
            . ($phone !== '' ? '<p>Registered mobile: ' . htmlspecialchars($phone) . '</p>' : '')
            . '<p>If this was not you, change your password immediately.</p>'
        );

        self::send($email, 'Solqam sign-in — your username', $html);
    }

    public static function sendOtp(string $email, string $code, string $purpose = 'signup'): bool
    {
        $label = $purpose === 'password_reset' ? 'password reset' : 'email verification';
        $html = self::wrap(
            'Verification code',
            '<p>Your Solqam ' . htmlspecialchars($label) . ' code is:</p>'
            . '<p style="font-size:32px;letter-spacing:8px;font-weight:800;color:#0B30E6;">' . htmlspecialchars($code) . '</p>'
            . '<p>This code is valid for 10 minutes. Do not share it with anyone.</p>'
            . '<p>If you did not request this code, you can ignore this email.</p>'
        );

        return self::send($email, 'Solqam verification code', $html);
    }

    public static function orderUpdate(string $email, string $orderNumber, string $status, string $extra = ''): void
    {
        $html = self::wrap(
            'Order update',
            '<p>Your Solqam order <strong>' . htmlspecialchars($orderNumber) . '</strong> is now <strong>' . htmlspecialchars($status) . '</strong>.</p>'
            . ($extra !== '' ? '<p>' . htmlspecialchars($extra) . '</p>' : '')
        );
        self::send($email, 'Solqam order ' . $orderNumber . ' — ' . $status, $html);
    }

    protected static function wrap(string $heading, string $inner): string
    {
        return '<div style="font-family:Arial,sans-serif;max-width:560px;margin:0 auto;color:#0F172A;">'
            . '<h2 style="color:#0B30E6;">' . htmlspecialchars($heading) . '</h2>'
            . $inner
            . '<p style="margin-top:24px;color:#64748B;font-size:13px;">— Solqam Market Place<br>info@solqam.com</p>'
            . '</div>';
    }

    protected static function archive(string $to, string $subject, string $html): void
    {
        $dir = WRITEPATH . 'mailbox';
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $file = $dir . DIRECTORY_SEPARATOR . date('Ymd_His') . '_' . preg_replace('/[^a-z0-9]+/i', '_', $to) . '.html';
        $body = '<h3>' . htmlspecialchars($subject) . '</h3><p>To: ' . htmlspecialchars($to) . '</p>' . $html;
        $written = @file_put_contents($file, $body);
        if ($written === false) {
            log_message('error', 'Mailbox archive failed: ' . $file);
        }
    }

    protected static function writeStatus(string $text): void
    {
        $dir = WRITEPATH . 'mailbox';
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents($dir . DIRECTORY_SEPARATOR . 'last_status.txt', date('c') . "\n" . $text);
    }
}
