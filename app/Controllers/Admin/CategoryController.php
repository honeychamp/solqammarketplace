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
        foreach ($categories as &$cat) {
            $cat['effective_commission'] = $this->commissionService->rateForCategory((int) $cat['id']);
        }
        unset($cat);

        return view('admin/categories/index', [
            'title'      => 'Manage Categories — Solqam Admin Console',
            'categories' => $categories,
        ]);
    }

    public function store()
    {
        $name = trim((string) $this->request->getPost('name'));
        if ($name === '') {
            return redirect()->back()->with('error', 'Category name is required.');
        }

        $parentId = $this->request->getPost('parent_id') ?: null;
        $rate     = $this->parseCommissionPercent($parentId !== null);

        $this->categoryModel->insert([
            'name'               => $name,
            'slug'               => $this->categoryModel->uniqueSlug($name),
            'description'        => $this->request->getPost('description'),
            'parent_id'          => $parentId,
            'icon'               => $this->request->getPost('icon'),
            'commission_percent' => $rate,
            'image'              => $this->storeCategoryImage(),
            'is_active'          => 1,
        ]);

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

        $payload = [
            'commission_percent' => $this->parseCommissionPercent($parentId !== null && $parentId !== ''),
        ];

        $name = trim((string) $this->request->getPost('name'));
        if ($name !== '') {
            $payload['name'] = $name;
            $payload['slug'] = $this->categoryModel->uniqueSlug($name, $id);
        }

        if ($this->request->getPost('description') !== null) {
            $payload['description'] = $this->request->getPost('description');
        }

        $image = $this->storeCategoryImage();
        if ($image) {
            $payload['image'] = $image;
        }

        $this->categoryModel->update($id, $payload);

        return $this->redirectAfterHub('/admin/categories', 'success', 'Category updated.');
    }

    public function delete($id)
    {
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
        if (! $img || ! $img->isValid() || $img->hasMoved()) {
            return null;
        }

        $mime = (string) $img->getMimeType();
        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
            return null;
        }

        if ($img->getSize() > 2 * 1024 * 1024) {
            return null;
        }

        $uploadDir = FCPATH . 'uploads/categories';
        if (! is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $newName = $img->getRandomName();
        $img->move($uploadDir, $newName);

        return base_url('uploads/categories/' . $newName);
    }
}
