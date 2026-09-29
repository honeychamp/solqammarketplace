<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BannerModel;

class BannerController extends BaseController
{
    public function index()
    {
        $banners = (new BannerModel())->orderBy('sort_order', 'ASC')->findAll();
        return view('admin/banners/index', [
            'title'   => 'Homepage Banners — Solqam Admin',
            'banners' => $banners,
        ]);
    }

    public function store()
    {
        (new BannerModel())->insert([
            'title'      => $this->request->getPost('title'),
            'subtitle'   => $this->request->getPost('subtitle'),
            'image_path' => $this->request->getPost('image_path'),
            'link_url'   => $this->request->getPost('link_url'),
            'placement'  => $this->request->getPost('placement') ?: 'hero',
            'sort_order' => (int) ($this->request->getPost('sort_order') ?? 0),
            'is_active'  => 1,
            'starts_at'  => $this->normalizeDateTime($this->request->getPost('starts_at')),
            'ends_at'    => $this->normalizeDateTime($this->request->getPost('ends_at')),
        ]);
        return redirect()->to('/admin/banners')->with('success', 'Banner saved.');
    }

    public function delete($id)
    {
        (new BannerModel())->delete((int) $id);
        return redirect()->to('/admin/banners')->with('success', 'Banner removed.');
    }

    protected function normalizeDateTime($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return str_replace('T', ' ', $value);
    }
}
