<?php

namespace App\Controllers\Api\V1;

use App\Models\ProductModel;
use App\Models\ReviewModel;

class ProductsController extends BaseApiController
{
    protected ProductModel $productModel;
    protected ReviewModel $reviewModel;

    public function __construct()
    {
        $this->productModel = new ProductModel();
        $this->reviewModel  = new ReviewModel();
    }

    public function index()
    {
        $filters = [
            'category_id'   => $this->request->getGet('category_id'),
            'category_slug' => $this->request->getGet('category'),
            'search'        => $this->request->getGet('search') ?? $this->request->getGet('q'),
            'seller_id'     => $this->request->getGet('seller_id'),
            'min_price'     => $this->request->getGet('min_price'),
            'max_price'     => $this->request->getGet('max_price'),
            'sort'          => $this->request->getGet('sort') ?? 'latest',
            'limit'         => (int) ($this->request->getGet('limit') ?? 20),
            'page'          => (int) ($this->request->getGet('page') ?? 1),
        ];

        $products = $this->productModel->getCatalog($filters);

        return $this->respondSuccess($products, 'Products retrieved successfully.');
    }

    public function show($id = null)
    {
        if (!$id) {
            return $this->respondFail('Product ID required.', null, 400);
        }

        $product = $this->productModel->getDetailedProduct((int) $id);
        if (!$product) {
            return $this->respondFail('Product not found.', null, 404);
        }

        $reviews = $this->reviewModel->getProductReviews((int) $id);
        $ratingStats = $this->reviewModel->getProductRatingStats((int) $id);

        $product['reviews'] = $reviews;
        $product['rating']  = $ratingStats;

        return $this->respondSuccess($product, 'Product detail retrieved.');
    }
}
