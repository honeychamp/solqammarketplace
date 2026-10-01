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
        return $this->respondSuccess($this->categoryModel->getTree(), 'Categories retrieved.');
    }

    public function show($id = null)
    {
        $category = is_numeric($id)
            ? $this->categoryModel->find($id)
            : $this->categoryModel->where('slug', $id)->first();

        if (!$category) {
            return $this->respondFail('Category not found.', null, 404);
        }

        $category['breadcrumb']    = $this->categoryModel->breadcrumb((int) $category['id']);
        $category['descendant_ids'] = $this->categoryModel->getSelfAndDescendantIds((int) $category['id']);
        $category['children']      = $this->categoryModel->where('parent_id', $category['id'])->where('is_active', 1)->orderBy('name', 'ASC')->findAll();

        return $this->respondSuccess($category, 'Category details.');
    }
}
