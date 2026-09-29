<?php

namespace App\Services\Config;

class DotEnvWriter
{
    /**
     * Update or append keys in ROOTPATH/.env
     *
     * @param array<string, string> $values
     */
    public static function upsert(array $values): bool
    {
        $path = ROOTPATH . '.env';
        if (! is_file($path) || ! is_writable($path)) {
            return false;
        }

        $raw = (string) file_get_contents($path);
        foreach ($values as $key => $value) {
            $line = $key . ' = ' . self::encode((string) $value);
            $pattern = '/^#?\s*' . preg_quote($key, '/') . '\s*=.*$/m';
            if (preg_match($pattern, $raw)) {
                $raw = preg_replace($pattern, $line, $raw, 1) ?? $raw;
            } else {
                $raw = rtrim($raw) . "\n" . $line . "\n";
            }
        }

        return file_put_contents($path, $raw) !== false;
    }

    protected static function encode(string $value): string
    {
        if ($value === '' || preg_match('/[\s#"\'\\\\]/', $value)) {
            return "'" . str_replace(["\\", "'"], ['\\\\', "\\'"], $value) . "'";
        }

        return $value;
    }
}
