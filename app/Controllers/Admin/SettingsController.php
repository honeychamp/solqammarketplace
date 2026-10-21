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
            $gid = trim((string) $this->request->getPost('google_oauth_client_id'));
            SettingService::set('google_oauth_client_id', $gid);
            $gsec = trim((string) $this->request->getPost('google_oauth_client_secret'));
            if ($gsec !== '') {
                SettingService::set('google_oauth_client_secret', $gsec);
            }
            $fid = trim((string) $this->request->getPost('facebook_app_id'));
            SettingService::set('facebook_app_id', $fid);
            $fsec = trim((string) $this->request->getPost('facebook_app_secret'));
            if ($fsec !== '') {
                SettingService::set('facebook_app_secret', $fsec);
            }
            AuditService::log('settings_update', 'platform');
            return redirect()->to('/admin/settings')->with('success', 'Platform settings saved.');
        }

        return view('admin/settings/index', [
            'title' => 'Platform settings — Solqam Admin',
            'sla_confirm_hours' => SettingService::int('sla_confirm_hours', 24),
            'sla_ship_hours'    => SettingService::int('sla_ship_hours', 72),
            'cod_max_open'      => SettingService::int('cod_max_open', 5),
            'auth_max_hits'     => SettingService::int('auth_max_hits', 8),
            'google_oauth_client_id' => SettingService::get('google_oauth_client_id'),
            'google_secret_set'      => SettingService::get('google_oauth_client_secret') !== '',
            'facebook_app_id'        => SettingService::get('facebook_app_id'),
            'facebook_secret_set'    => SettingService::get('facebook_app_secret') !== '',
            'google_callback'        => (new \App\Services\Auth\SocialAuthService())->callbackUrl('google'),
            'facebook_callback'      => (new \App\Services\Auth\SocialAuthService())->callbackUrl('facebook'),
        ]);
    }
}
