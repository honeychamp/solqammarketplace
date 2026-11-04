<?php

namespace App\Controllers\Customer;

use App\Controllers\BaseController;
use App\Models\BannerModel;
use App\Models\CategoryModel;
use App\Models\ProductModel;
use App\Services\Catalog\PricingService;

class HomeController extends BaseController
{
    public function index()
    {
        $categoryModel = new CategoryModel();
        $productModel  = new ProductModel();
        $pricing       = new PricingService();
        $bannerModel   = new BannerModel();

        $perPage = 30;
        $page    = max(1, (int) ($this->request->getGet('page') ?? 1));
        $pack    = storefront_remember('sf_home_' . $page, 180, static function () use ($page, $perPage, $productModel, $pricing, $bannerModel, $categoryModel): array {
            $categories = [];
            try {
                $categories = category_tree();
                if ($categories === []) {
                    $categories = $categoryModel->where('is_active', 1)
                        ->groupStart()
                            ->where('parent_id', null)
                            ->orWhere('parent_id', 0)
                        ->groupEnd()
                        ->orderBy('sort_order', 'ASC')
                        ->findAll();
                }
            } catch (\Throwable $e) {
                $categories = [];
            }

            $filters = ['limit' => $perPage, 'page' => $page, 'sort' => 'latest'];
            $homeTotal = 0;
            $homeProducts = [];
            $homePages = 1;
            try {
                $homeTotal = $productModel->countCatalog($filters);
                $homePages = max(1, (int) ceil($homeTotal / $perPage));
                $usePage = min($page, $homePages);
                $filters['page'] = $usePage;
                $homeProducts = $productModel->getCatalog($filters);
            } catch (\Throwable $e) {
                log_message('error', 'Homepage catalog: ' . $e->getMessage());
            }

            $flashSale = null;
            $flashProducts = [];
            $megaSale = null;
            $megaProducts = [];
            $heroBanners = [];
            $sideBanners = [];
            try {
                $flashSale     = $pricing->getActiveSale('flash');
                $flashProducts = $pricing->getCampaignProducts('flash', 12);
                $megaSale      = $pricing->getActiveSale('mega');
                $megaProducts  = $pricing->getCampaignProducts('mega', 8);
                $heroBanners   = $bannerModel->forPlacement('hero');
                $sideBanners   = $bannerModel->forPlacement('side');
                $flashMap      = $pricing->getFlashPriceMap();
                foreach ($homeProducts as &$p) {
                    if (isset($flashMap[(int) $p['id']])) {
                        $p['flash_price'] = $flashMap[(int) $p['id']];
                    }
                }
                unset($p);
            } catch (\Throwable $e) {
            }

            return [
                'categories'    => is_array($categories) ? $categories : [],
                'homeTotal'     => $homeTotal,
                'homePages'     => $homePages,
                'homeProducts'  => is_array($homeProducts) ? $homeProducts : [],
                'flashSale'     => $flashSale,
                'flashProducts' => is_array($flashProducts) ? $flashProducts : [],
                'megaSale'      => $megaSale,
                'megaProducts'  => is_array($megaProducts) ? $megaProducts : [],
                'heroBanners'   => is_array($heroBanners) ? $heroBanners : [],
                'sideBanners'   => is_array($sideBanners) ? $sideBanners : [],
            ];
        });

        $categories    = $pack['categories'] ?? [];
        $homeTotal     = (int) ($pack['homeTotal'] ?? 0);
        $homePages     = (int) ($pack['homePages'] ?? 1);
        $homeProducts  = $pack['homeProducts'] ?? [];
        $flashSale     = $pack['flashSale'] ?? null;
        $flashProducts = $pack['flashProducts'] ?? [];
        $megaSale      = $pack['megaSale'] ?? null;
        $megaProducts  = $pack['megaProducts'] ?? [];
        $heroBanners   = $pack['heroBanners'] ?? [];
        $sideBanners   = $pack['sideBanners'] ?? [];

        $hasFlashDeal = ! empty($flashSale) && ! empty($flashProducts);

        $data = [
            'title'            => 'Solqam Marketplace — Pakistan Multi-Vendor Marketplace',
            'categories'       => is_array($categories) ? $categories : [],
            'homeProducts'     => is_array($homeProducts) ? $homeProducts : [],
            'homeTotal'        => $homeTotal,
            'homePages'        => $homePages,
            'homePage'         => $page,
            'hasFlashDeal'     => $hasFlashDeal,
            'flashSale'        => $flashSale,
            'flashProducts'    => is_array($flashProducts) ? $flashProducts : [],
            'megaSale'         => $megaSale,
            'megaProducts'     => is_array($megaProducts) ? $megaProducts : [],
            'heroBanners'      => is_array($heroBanners) ? $heroBanners : [],
            'sideBanners'      => is_array($sideBanners) ? $sideBanners : [],
        ];

        try {
            return view('customer/home', $data);
        } catch (\Throwable $e) {
            log_message('error', 'Homepage view: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            $data['homeProducts']  = [];
            $data['hasFlashDeal']  = false;
            $data['flashProducts'] = [];
            $data['megaProducts']  = [];
            $data['heroBanners']   = [];
            $data['sideBanners']   = [];
            try {
                return view('customer/home', $data);
            } catch (\Throwable $e2) {
                log_message('error', 'Homepage fallback: ' . $e2->getMessage());

                return $this->response->setStatusCode(200)->setBody(
                    '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Solqam</title></head><body>'
                    . '<p>Solqam is up. Catalog view failed. Check writable/logs.</p>'
                    . '<p><a href="' . site_url('login') . '">Login</a> · <a href="' . site_url('shop') . '">Shop</a></p>'
                    . '</body></html>'
                );
            }
        }
    }
}
