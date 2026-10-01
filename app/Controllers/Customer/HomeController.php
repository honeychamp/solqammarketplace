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

        $categories = [];
        try {
            $categories = $categoryModel->where('is_active', 1)->where('parent_id', null)->orderBy('sort_order', 'ASC')->findAll();
        } catch (\Throwable $e) {
            $categories = [];
        }
        if (empty($categories)) {
            $categories = $categoryModel->getActiveCategories();
        }

        $perPage = 30;
        $page    = max(1, (int) ($this->request->getGet('page') ?? 1));
        $filters = ['limit' => $perPage, 'page' => $page, 'sort' => 'latest'];
        $homeTotal = $productModel->countCatalog($filters);
        $homePages = max(1, (int) ceil($homeTotal / $perPage));
        if ($page > $homePages) {
            $page = $homePages;
            $filters['page'] = $page;
        }
        $homeProducts = $productModel->getCatalog($filters);
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
            // Tables not migrated yet
        }

        $hasFlashDeal = ! empty($flashSale) && ! empty($flashProducts);

        return view('customer/home', [
            'title'            => 'Solqam Market Place — Pakistan Multi-Vendor Marketplace',
            'categories'       => $categories,
            'homeProducts'     => $homeProducts,
            'homeTotal'        => $homeTotal,
            'homePages'        => $homePages,
            'homePage'         => $page,
            'hasFlashDeal'     => $hasFlashDeal,
            'flashSale'        => $flashSale,
            'flashProducts'    => $flashProducts,
            'megaSale'         => $megaSale,
            'megaProducts'     => $megaProducts,
            'heroBanners'      => $heroBanners,
            'sideBanners'      => $sideBanners,
        ]);
    }
}
