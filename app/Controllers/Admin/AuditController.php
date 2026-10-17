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
                ->get()
                ->getResultArray();
            [$rows, $pager] = hub_paginate_array($rows, 25);
        } catch (\Throwable $e) {
            $pager = null;
        }

        return view('admin/audit/index', [
            'title' => 'Audit log — Solqam Admin',
            'rows'  => $rows,
            'pager' => $pager ?? null,
        ]);
    }
}
