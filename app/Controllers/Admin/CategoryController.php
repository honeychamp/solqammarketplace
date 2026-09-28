<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CategoryModel;

class CategoryController extends BaseController
{
    protected CategoryModel $categoryModel;

    public function __construct()
    {
        $this->categoryModel = new CategoryModel();
    }

    public function index()
    {
        $categories = $this->categoryModel->orderBy('id', 'DESC')->findAll();

        return view('admin/categories/index', [
            'title'      => 'Manage Categories — Solqam Admin Console',
            'categories' => $categories,
        ]);
    }

    public function store()
    {
        $name = $this->request->getPost('name');
        if (!$name) {
            return redirect()->back()->with('error', 'Category name is required.');
        }

        $slug = url_title($name, '-', true);
        $this->categoryModel->insert([
            'name'        => $name,
            'slug'        => $slug,
            'description' => $this->request->getPost('description'),
            'parent_id'   => $this->request->getPost('parent_id') ?: null,
            'icon'        => $this->request->getPost('icon'),
            'is_active'   => 1,
        ]);

        return redirect()->to('/admin/categories')->with('success', 'Category created successfully.');
    }

    public function delete($id)
    {
        $this->categoryModel->delete($id);
        return redirect()->to('/admin/categories')->with('success', 'Category removed.');
    }
}
