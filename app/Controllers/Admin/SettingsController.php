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
            SettingService::set('gift_wrap_enabled', $this->request->getPost('gift_wrap_enabled') ? '1' : '0');
            $gfee = max(0, (int) $this->request->getPost('gift_wrap_fee'));
            SettingService::set('gift_wrap_fee', (string) $gfee);
            foreach ([
                'collect_jazzcash_name',
                'collect_jazzcash_number',
                'collect_easypaisa_name',
                'collect_easypaisa_number',
                'collect_bank_name',
                'collect_bank_title',
                'collect_bank_account',
            ] as $payKey) {
                SettingService::set($payKey, trim((string) $this->request->getPost($payKey)));
            }
            AuditService::log('settings_update', 'platform');
            return redirect()->to('/admin/settings')->with('success', 'Platform settings saved.');
        }

        $pay = SettingService::collectionAccounts();

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
            'collect_jazzcash_name'    => $pay['jazzcash_name'],
            'collect_jazzcash_number'  => $pay['jazzcash_number'],
            'collect_easypaisa_name'   => $pay['easypaisa_name'],
            'collect_easypaisa_number' => $pay['easypaisa_number'],
            'collect_bank_name'        => $pay['bank_name'],
            'collect_bank_title'       => $pay['bank_title'],
            'collect_bank_account'     => $pay['bank_account'],
            'gift_wrap_enabled'        => SettingService::get('gift_wrap_enabled', '1') !== '0',
            'gift_wrap_fee'            => SettingService::int('gift_wrap_fee', 150),
        ]);
    }
}
