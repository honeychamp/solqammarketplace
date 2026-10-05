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
        $title = trim((string) $this->request->getPost('title'));
        if ($title === '') {
            return redirect()->back()->with('error', 'Banner title is required.');
        }

        $imagePath = $this->storeBannerImage();
        if ($imagePath === null) {
            $imagePath = $this->normalizePublicImagePath((string) $this->request->getPost('image_path'));
        }

        $model = new BannerModel();
        $row = [
            'title'       => $title,
            'subtitle'    => $this->request->getPost('subtitle'),
            'badge_text'  => $this->request->getPost('badge_text'),
            'button_text' => $this->request->getPost('button_text'),
            'image_path'  => $imagePath !== '' ? $imagePath : null,
            'link_url'    => $this->request->getPost('link_url'),
            'placement'   => $this->request->getPost('placement') ?: 'hero',
            'sort_order'  => (int) ($this->request->getPost('sort_order') ?? 0),
            'is_active'   => 1,
            'starts_at'   => $this->normalizeDateTime($this->request->getPost('starts_at')),
            'ends_at'     => $this->normalizeDateTime($this->request->getPost('ends_at')),
        ];
        $fields = $model->db->getFieldNames('banners');
        $insert = [];
        foreach ($row as $key => $value) {
            if (in_array($key, $fields, true)) {
                $insert[$key] = $value;
            }
        }
        try {
            $model->insert($insert);
        } catch (\Throwable $e) {
            log_message('error', 'Banner save: ' . $e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Banner could not be saved. Check image size/type and try again.');
        }

        return redirect()->to('/admin/banners')->with('success', 'Banner saved. It now shows on the homepage.');
    }

    public function delete($id)
    {
        (new BannerModel())->delete((int) $id);
        return redirect()->to('/admin/banners')->with('success', 'Banner removed.');
    }

    protected function storeBannerImage(): ?string
    {
        $img = $this->request->getFile('image');
        if (! $img || ! $img->isValid() || $img->hasMoved()) {
            return null;
        }

        $mime = (string) $img->getMimeType();
        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
            return null;
        }

        if ($img->getSize() > 4 * 1024 * 1024) {
            return null;
        }

        $uploadDir = FCPATH . 'uploads/banners';
        if (! is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $newName = $img->getRandomName();
        $img->move($uploadDir, $newName);

        return 'uploads/banners/' . $newName;
    }

    protected function normalizePublicImagePath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $path) || str_starts_with($path, '//')) {
            return $path;
        }

        return ltrim($path, '/');
    }

    protected function normalizeDateTime($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || str_starts_with($value, '0000-00-00')) {
            return null;
        }

        return str_replace('T', ' ', $value);
    }
}
