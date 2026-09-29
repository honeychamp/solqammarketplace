<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ProductModel;

class SeoController extends BaseController
{
    public function sitemap()
    {
        $products = (new ProductModel())->select('id, updated_at')->where('status', 'active')->orderBy('id', 'DESC')->findAll(2000);
        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $xml .= '<url><loc>' . htmlspecialchars(site_url('/')) . '</loc></url>';
        $xml .= '<url><loc>' . htmlspecialchars(site_url('shop')) . '</loc></url>';
        foreach ($products as $p) {
            $xml .= '<url><loc>' . htmlspecialchars(site_url('product/' . $p['id'])) . '</loc></url>';
        }
        $xml .= '</urlset>';
        return $this->response->setHeader('Content-Type', 'application/xml')->setBody($xml);
    }
}
