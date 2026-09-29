<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\Platform\AuditService;
use App\Services\Platform\SettingService;

class SettingsController extends BaseController
{
    public function index()
    {
        if ($this->request->is('post')) {
            SettingService::set('sla_confirm_hours', (string) (int) $this->request->getPost('sla_confirm_hours'));
            SettingService::set('sla_ship_hours', (string) (int) $this->request->getPost('sla_ship_hours'));
            SettingService::set('cod_max_open', (string) (int) $this->request->getPost('cod_max_open'));
            SettingService::set('auth_max_hits', (string) (int) $this->request->getPost('auth_max_hits'));
            AuditService::log('settings_update', 'platform');
            return redirect()->to('/admin/settings')->with('success', 'Platform settings saved.');
        }

        return view('admin/settings/index', [
            'title' => 'Platform settings — Solqam Admin',
            'sla_confirm_hours' => SettingService::int('sla_confirm_hours', 24),
            'sla_ship_hours'    => SettingService::int('sla_ship_hours', 72),
            'cod_max_open'      => SettingService::int('cod_max_open', 5),
            'auth_max_hits'     => SettingService::int('auth_max_hits', 8),
        ]);
    }
}
