<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use Config\Database;

class AuditController extends BaseController
{
    public function index()
    {
        $rows = [];
        try {
            $rows = Database::connect()->table('audit_logs')
                ->orderBy('id', 'DESC')
                ->limit(200)
                ->get()
                ->getResultArray();
        } catch (\Throwable $e) {
        }

        return view('admin/audit/index', [
            'title' => 'Audit log — Solqam Admin',
            'rows'  => $rows,
        ]);
    }
}
