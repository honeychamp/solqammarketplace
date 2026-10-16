<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\MarketingEventLogModel;
use App\Models\MarketingSettingModel;
use App\Services\Marketing\MarketingService;
use App\Services\Platform\AuditService;

class MarketingController extends BaseController
{
    public function settings()
    {
        $model = new MarketingSettingModel();
        if ($this->request->is('post')) {
            $gtm = strtoupper(trim((string) $this->request->getPost('gtm_id')));
            $ga4 = strtoupper(trim((string) $this->request->getPost('ga4_id')));
            if ($gtm !== '' && ! preg_match('/^GTM-[A-Z0-9]+$/', $gtm)) {
                return redirect()->back()->withInput()->with('error', 'GTM ID must look like GTM-XXXX.');
            }
            if ($ga4 !== '' && ! preg_match('/^G-[A-Z0-9]+$/', $ga4)) {
                return redirect()->back()->withInput()->with('error', 'GA4 ID must look like G-XXXX.');
            }
            $model->saveCurrent($this->request->getPost() ?? []);
            AuditService::log('marketing_settings', 'platform');

            return redirect()->to('/admin/marketing')->with('success', 'Marketing tracking saved. Tokens stay on the server and are never printed in the storefront.');
        }

        $s = $model->current();

        return view('admin/marketing/settings', [
            'title'    => 'Marketing tracking — Solqam Admin',
            'settings' => $s,
            'masks'    => [
                'meta_capi_token'  => MarketingSettingModel::mask($s['meta_capi_token'] ?? ''),
                'tiktok_api_token' => MarketingSettingModel::mask($s['tiktok_api_token'] ?? ''),
            ],
        ]);
    }

    public function logs()
    {
        $model  = new MarketingEventLogModel();
        $status = (string) $this->request->getGet('status');
        $plat   = (string) $this->request->getGet('platform');
        $q      = $model->orderBy('id', 'DESC');
        if (in_array($status, ['sent', 'error', 'queued'], true)) {
            $q->where('status', $status);
        }
        if ($plat !== '') {
            $q->where('platform', $plat);
        }
        $rows = $q->paginate(40);

        return view('admin/marketing/logs', [
            'title'  => 'Marketing event logs — Solqam Admin',
            'rows'   => $rows,
            'pager'  => $model->pager,
            'status' => $status,
            'platform' => $plat,
        ]);
    }

    public function testEvent()
    {
        if (! $this->request->is('post')) {
            return redirect()->to('/admin/marketing');
        }
        MarketingService::track('ViewContent', [
            'event_id'         => 'test_' . bin2hex(random_bytes(4)),
            'value'            => 1,
            'content_ids'      => ['TEST'],
            'content_name'     => 'Solqam test event',
            'content_type'     => 'product',
            'contents'         => [['id' => 'TEST', 'quantity' => 1, 'item_price' => 1]],
            'items'            => [['item_id' => 'TEST', 'item_name' => 'Solqam test event', 'price' => 1, 'quantity' => 1]],
        ]);

        return redirect()->to('/admin/marketing/logs')->with('success', 'Test ViewContent sent. Check Meta/TikTok test tools and this log.');
    }
}
