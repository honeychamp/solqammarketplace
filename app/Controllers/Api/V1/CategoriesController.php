<?php

namespace App\Controllers\Api\V1;

use App\Models\CategoryModel;

class CategoriesController extends BaseApiController
{
    protected CategoryModel $categoryModel;

    public function __construct()
    {
        $this->categoryModel = new CategoryModel();
    }

    public function index()
    {
        $categories = $this->categoryModel->getActiveCategories();
        return $this->respondSuccess($categories, 'Categories retrieved.');
    }

    public function show($id = null)
    {
        $category = is_numeric($id)
            ? $this->categoryModel->find($id)
            : $this->categoryModel->where('slug', $id)->first();

        if (!$category) {
            return $this->respondFail('Category not found.', null, 404);
        }

        return $this->respondSuccess($category, 'Category details.');
    }
}
