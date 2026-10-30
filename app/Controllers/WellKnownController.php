<?php

namespace App\Controllers;

use Config\Twa;

class WellKnownController extends BaseController
{
    public function assetlinks()
    {
        $cfg  = config(Twa::class);
        $fps  = [];
        foreach ($cfg->sha256Fingerprints as $fp) {
            $fp = strtoupper(trim(str_replace(' ', '', $fp)));
            if ($fp !== '') {
                $fps[] = $fp;
            }
        }

        $payload = [[
            'relation' => ['delegate_permission/common.handle_all_urls'],
            'target'   => [
                'namespace'              => 'android_app',
                'package_name'           => $cfg->packageId,
                'sha256_cert_fingerprints' => $fps,
            ],
        ]];

        return $this->response
            ->setHeader('Content-Type', 'application/json; charset=UTF-8')
            ->setHeader('Cache-Control', 'public, max-age=300')
            ->setJSON($payload);
    }
}
