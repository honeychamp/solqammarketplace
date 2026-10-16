<?php

namespace App\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Serves product / category photos from public/uploads, project uploads/, or writable/uploads.
 * Live LiteSpeed often sends /uploads/* into index.php instead of the static file.
 */
class MediaController extends BaseController
{
    public function uploads(string $path = '')
    {
        $path = str_replace(['\\', "\0"], '/', $path);
        $path = ltrim($path, '/');
        if ($path === '' || str_contains($path, '..') || ! preg_match('#^(products|categories|banners|reviews|returns|pay-later)/[A-Za-z0-9][A-Za-z0-9._-]*$#', $path)) {
            throw PageNotFoundException::forPageNotFound();
        }

        $rel = str_replace('/', DIRECTORY_SEPARATOR, $path);
        $candidates = [
            FCPATH . 'uploads' . DIRECTORY_SEPARATOR . $rel,
            ROOTPATH . 'uploads' . DIRECTORY_SEPARATOR . $rel,
            WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . $rel,
            ROOTPATH . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $rel,
        ];

        $file = null;
        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                $file = $candidate;
                break;
            }
        }
        if ($file === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $mime = 'application/octet-stream';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $detected = finfo_file($finfo, $file);
                finfo_close($finfo);
                if (is_string($detected) && $detected !== '') {
                    $mime = $detected;
                }
            }
        }

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Cache-Control', 'public, max-age=604800')
            ->setBody((string) file_get_contents($file));
    }
}
