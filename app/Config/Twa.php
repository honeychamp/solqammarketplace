<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Android Play Store TWA — package + signing fingerprints.
 * After the first AAB upload: Play Console → App integrity → App signing.
 * Paste BOTH SHA-256 values (App signing key + Upload key). Then upload this file
 * and delete public/.well-known/assetlinks.json on live so PHP serves this list
 * at https://solqam.com/.well-known/assetlinks.json
 */
class Twa extends BaseConfig
{
    public string $packageId = 'com.solqam.marketplace';

    /** @var list<string> */
    public array $sha256Fingerprints = [
        // 'AB:CD:EF:...' App signing key
        // 'AB:CD:EF:...' Upload key
    ];
}
