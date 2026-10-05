<?php

namespace App\Controllers\Customer;

use App\Controllers\BaseController;
use App\Models\CategoryModel;
use App\Services\Commission\CommissionService;

class PagesController extends BaseController
{
    public function commission()
    {
        $service = new CommissionService();
        $categories = (new CategoryModel())->withPaths(
            (new CategoryModel())->orderBy('parent_id', 'ASC')->orderBy('name', 'ASC')->findAll()
        );
        foreach ($categories as &$cat) {
            $cat['effective_commission'] = $service->rateForCategory((int) $cat['id']);
        }
        unset($cat);

        return view('customer/pages/commission', [
            'title'      => 'Commission Structure — Solqam Marketplace',
            'percentage' => $service->getCommissionRate(),
            'categories' => $categories,
        ]);
    }

    public function policies()
    {
        return view('customer/pages/policies', [
            'title' => 'Seller Policies — Solqam Marketplace',
        ]);
    }

    public function fulfillment()
    {
        return view('customer/pages/fulfillment', [
            'title' => 'Fulfillment by Solqam — Solqam Marketplace',
        ]);
    }

    public function returns()
    {
        return view('customer/pages/returns', [
            'title' => 'Returns & Refunds — Solqam Marketplace',
        ]);
    }

    public function shipping()
    {
        return view('customer/pages/shipping', [
            'title' => 'Shipping & Delivery — Solqam Marketplace',
            'zones' => (new \App\Models\ShippingZoneModel())->orderBy('city', 'ASC')->findAll(),
        ]);
    }
}
