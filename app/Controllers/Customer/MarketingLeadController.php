<?php

namespace App\Controllers\Customer;

use App\Controllers\BaseController;
use App\Services\Marketing\MarketingService;

class MarketingLeadController extends BaseController
{
    public function capture()
    {
        MarketingService::rememberLead($this->request->getPost() ?? []);
        MarketingService::captureClickIds();

        return $this->response->setJSON(['ok' => true]);
    }
}
