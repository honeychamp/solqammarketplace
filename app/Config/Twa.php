<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Android Play Store TWA (Trusted Web Activity) — package + signing fingerprints.
 * After Play Console → App integrity → App signing, paste BOTH SHA-256 hashes here.
 */
class Twa extends BaseConfig
{
    public string $packageId = 'com.solqam.marketplace';

    /** @var list<string> */
    public array $sha256Fingerprints = [
        // 'AB:CD:...' Play App signing key
        // 'AB:CD:...' Upload key
    ];
}
