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
        $categories = (new CategoryModel())->getActiveCategories();
        foreach ($categories as &$cat) {
            $cat['effective_commission'] = $service->rateForCategory((int) $cat['id']);
        }
        unset($cat);

        return view('customer/pages/commission', [
            'title'      => 'Commission Structure — Solqam Market Place',
            'percentage' => $service->getCommissionRate(),
            'categories' => $categories,
        ]);
    }

    public function policies()
    {
        return view('customer/pages/policies', [
            'title' => 'Seller Policies — Solqam Market Place',
        ]);
    }

    public function fulfillment()
    {
        return view('customer/pages/fulfillment', [
            'title' => 'Fulfillment by Solqam — Solqam Market Place',
        ]);
    }

    public function returns()
    {
        return view('customer/pages/returns', [
            'title' => 'Returns & Refunds — Solqam Market Place',
        ]);
    }

    public function shipping()
    {
        return view('customer/pages/shipping', [
            'title' => 'Shipping & Delivery — Solqam Market Place',
        ]);
    }
}
