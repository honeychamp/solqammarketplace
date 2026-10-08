<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CategoryModel;
use App\Services\Commission\CommissionService;

class CategoryController extends BaseController
{
    protected CategoryModel $categoryModel;
    protected CommissionService $commissionService;

    public function __construct()
    {
        $this->categoryModel     = new CategoryModel();
        $this->commissionService = new CommissionService();
    }

    public function index()
    {
        $categories = $this->categoryModel->orderBy('parent_id', 'ASC')->orderBy('name', 'ASC')->findAll();
        $byId       = [];
        foreach ($categories as $row) {
            $byId[(int) $row['id']] = $row;
        }
        foreach ($categories as &$cat) {
            $cat['effective_commission'] = $this->commissionService->rateForCategory((int) $cat['id']);
            $cat['depth']                = $this->categoryModel->depthOf((int) $cat['id']);
            $parts                       = [$cat['name']];
            $pid                         = (int) ($cat['parent_id'] ?? 0);
            $guard                       = 0;
            while ($pid && isset($byId[$pid]) && $guard++ < 8) {
                array_unshift($parts, $byId[$pid]['name']);
                $pid = (int) ($byId[$pid]['parent_id'] ?? 0);
            }
            $cat['path_label']   = implode(' → ', $parts);
            $cat['parent_name']  = ! empty($cat['parent_id']) && isset($byId[(int) $cat['parent_id']])
                ? $byId[(int) $cat['parent_id']]['name']
                : 'Top-level';
        }
        unset($cat);

        return view('admin/categories/index', [
            'title'          => 'Manage Categories — Solqam Admin Console',
            'categories'     => $categories,
            'parentOptions'  => $this->categoryModel->optionsForParent(),
        ]);
    }

    public function store()
    {
        $name = trim((string) $this->request->getPost('name'));
        if ($name === '') {
            return redirect()->back()->with('error', 'Category name is required.');
        }

        $parentId = $this->request->getPost('parent_id') ?: null;
        if ($parentId) {
            $parentDepth = $this->categoryModel->depthOf((int) $parentId);
            if ($parentDepth < 1) {
                return redirect()->back()->with('error', 'Parent category was not found.');
            }
            if ($parentDepth >= 4) {
                return redirect()->back()->with('error', 'Categories stop at 4 levels (like Daraz). Choose a higher parent.');
            }
        }

        $rate = $this->parseCommissionPercent($parentId !== null);

        try {
            $image = $this->storeCategoryImage();
        } catch (\RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        $payload = [
            'name'        => $name,
            'slug'        => $this->categoryModel->uniqueSlug($name),
            'description' => $this->request->getPost('description'),
            'parent_id'   => $parentId,
            'icon'        => $this->request->getPost('icon'),
            'is_active'   => 1,
        ];
        if ($image) {
            $payload['image'] = $image;
        }

        if ($this->categoryModel->db->fieldExists('commission_percent', 'categories')) {
            $payload['commission_percent'] = $rate;
        }

        $this->categoryModel->insert($payload);

        return $this->redirectAfterHub('/admin/categories', 'success', 'Category created.');
    }

    public function update($id)
    {
        $id  = (int) $id;
        $cat = $this->categoryModel->find($id);
        if (! $cat) {
            return redirect()->to('/admin/categories')->with('error', 'Category not found.');
        }

        $parentId = $this->request->getPost('parent_id');
        if ($parentId === null) {
            $parentId = $cat['parent_id'] ?? null;
        } else {
            $parentId = $parentId === '' ? null : $parentId;
        }

        $payload = [];
        if ($this->categoryModel->db->fieldExists('commission_percent', 'categories')) {
            $payload['commission_percent'] = $this->parseCommissionPercent($parentId !== null && $parentId !== '');
        }

        $name = trim((string) $this->request->getPost('name'));
        if ($name !== '') {
            $payload['name'] = $name;
            $payload['slug'] = $this->categoryModel->uniqueSlug($name, $id);
        }

        if ($this->request->getPost('description') !== null) {
            $payload['description'] = $this->request->getPost('description');
        }

        try {
            $image = $this->storeCategoryImage();
        } catch (\RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
        if ($image) {
            $payload['image'] = $image;
        }

        if ($payload === []) {
            return redirect()->to('/admin/categories')->with('error', 'Nothing to update.');
        }

        if (isset($payload['image']) && ! $this->categoryModel->db->fieldExists('image', 'categories')) {
            return redirect()->back()->with('error', 'Category image column is missing. Refresh the page once, then try again.');
        }

        $this->categoryModel->update($id, $payload);

        $msg = isset($payload['image']) ? 'Category image saved.' : 'Category updated.';

        return $this->redirectAfterHub('/admin/categories', 'success', $msg);
    }

    public function delete($id)
    {
        $id  = (int) $id;
        $cat = $this->categoryModel->find($id);
        if (! $cat) {
            return redirect()->to('/admin/categories')->with('error', 'Category not found.');
        }

        $childCount = $this->categoryModel->where('parent_id', $id)->countAllResults();
        if ($childCount > 0) {
            return redirect()->to('/admin/categories')->with('error', 'Move or delete subcategories first. This category still has ' . $childCount . ' child rows.');
        }

        $this->categoryModel->delete($id);

        return redirect()->to('/admin/categories')->with('success', 'Category removed.');
    }

    protected function parseCommissionPercent(bool $allowInherit): ?float
    {
        $raw = $this->request->getPost('commission_percent');
        if ($raw === null || $raw === '') {
            return $allowInherit ? null : 10.0;
        }

        $pct = (float) $raw;
        if ($pct < 0 || $pct > 50) {
            return $allowInherit ? null : 10.0;
        }

        return round($pct, 2);
    }

    protected function storeCategoryImage(): ?string
    {
        $img = $this->request->getFile('image');
        if (! $img || $img->getError() === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (! $img->isValid() || $img->hasMoved()) {
            throw new \RuntimeException($img->getErrorString() ?: 'Category image could not be uploaded.');
        }

        if ($img->getSize() > 4 * 1024 * 1024) {
            throw new \RuntimeException('Category image must be 4 MB or smaller.');
        }

        $mime = strtolower((string) ($img->getMimeType() ?: $img->getClientMimeType()));
        $ext  = strtolower((string) ($img->getClientExtension() ?: $img->guessExtension()));
        $okMime = in_array($mime, ['image/jpeg', 'image/jpg', 'image/pjpeg', 'image/png', 'image/x-png', 'image/webp', 'image/gif'], true);
        $okExt  = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
        if (! $okMime && ! $okExt) {
            throw new \RuntimeException('Use a JPG, PNG, WEBP, or GIF image.');
        }

        $uploadDir = rtrim(FCPATH, '\\/') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'categories';
        if (! is_dir($uploadDir) && ! @mkdir($uploadDir, 0755, true) && ! is_dir($uploadDir)) {
            throw new \RuntimeException('Could not create uploads/categories. Check folder permissions.');
        }

        $safeExt = $okExt ? $ext : 'jpg';
        $newName = $img->getRandomName();
        if ($newName === '' || ! str_contains($newName, '.')) {
            $newName = bin2hex(random_bytes(8)) . '.' . $safeExt;
        }

        $img->move($uploadDir, $newName);

        return 'uploads/categories/' . $newName;
    }
}
