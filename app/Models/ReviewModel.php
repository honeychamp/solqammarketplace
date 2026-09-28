<?php

namespace App\Models;

use CodeIgniter\Model;

class ReviewModel extends Model
{
    protected $table            = 'reviews';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'product_id',
        'user_id',
        'order_id',
        'rating',
        'comment',
        'image_path',
        'is_approved',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getProductReviews(int $productId): array
    {
        return $this->select('reviews.*, users.name as reviewer_name')
            ->join('users', 'users.id = reviews.user_id')
            ->where('reviews.product_id', $productId)
            ->where('reviews.is_approved', 1)
            ->orderBy('reviews.id', 'DESC')
            ->findAll();
    }

    public function getProductRatingStats(int $productId): array
    {
        $res = $this->select('AVG(rating) as avg_rating, COUNT(id) as total_reviews')
            ->where('product_id', $productId)
            ->where('is_approved', 1)
            ->first();

        return [
            'average' => round((float) ($res['avg_rating'] ?? 0), 1),
            'count'   => (int) ($res['total_reviews'] ?? 0),
        ];
    }

    public function getRatingBreakdown(int $productId): array
    {
        $map  = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        $rows = $this->select('rating, COUNT(id) as total')
            ->where('product_id', $productId)
            ->where('is_approved', 1)
            ->groupBy('rating')
            ->findAll();

        foreach ($rows as $row) {
            $star = (int) $row['rating'];
            if (isset($map[$star])) {
                $map[$star] = (int) $row['total'];
            }
        }

        return $map;
    }
}
