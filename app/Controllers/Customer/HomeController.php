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

        $featuredProducts = $productModel->getCatalog(['limit' => 8, 'sort' => 'best_selling']);
        $flashSale = null;
        $flashProducts = [];
        $megaSale = null;
        $megaProducts = [];
        $heroBanners = [];
        $sideBanners = [];
        try {
            $flashSale     = $pricing->getActiveSale('flash');
            $flashProducts = $pricing->getCampaignProducts('flash', 8);
            $megaSale      = $pricing->getActiveSale('mega');
            $megaProducts  = $pricing->getCampaignProducts('mega', 8);
            $heroBanners   = $bannerModel->forPlacement('hero');
            $sideBanners   = $bannerModel->forPlacement('side');
        } catch (\Throwable $e) {
            // Tables not migrated yet
        }

        return view('customer/home', [
            'title'            => 'Solqam Market Place — Pakistan Multi-Vendor Marketplace',
            'categories'       => $categories,
            'featuredProducts' => $featuredProducts,
            'flashSale'        => $flashSale,
            'flashProducts'    => $flashProducts,
            'megaSale'         => $megaSale,
            'megaProducts'     => $megaProducts,
            'heroBanners'      => $heroBanners,
            'sideBanners'      => $sideBanners,
        ]);
    }
}
