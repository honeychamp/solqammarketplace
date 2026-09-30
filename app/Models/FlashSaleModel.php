<?php

namespace App\Models;

use CodeIgniter\Model;

class FlashSaleModel extends Model
{
    protected $table         = 'flash_sales';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['title', 'campaign_type', 'starts_at', 'ends_at', 'is_active', 'seller_join', 'rules_note'];
    protected $useTimestamps = true;

    public function getActive(?string $type = null): ?array
    {
        $rows = $this->getLiveCampaigns($type);

        return $rows[0] ?? null;
    }

    public function getLiveCampaigns(?string $type = null): array
    {
        $now = date('Y-m-d H:i:s');
        $builder = $this->where('is_active', 1)
            ->where('starts_at <=', $now)
            ->where('ends_at >=', $now);
        if ($type) {
            $builder->where('campaign_type', $type);
        }

        return $builder->orderBy('id', 'DESC')->findAll();
    }

    public function getOpenForSellerJoin(): array
    {
        $now = date('Y-m-d H:i:s');

        return $this->where('is_active', 1)
            ->where('seller_join', 1)
            ->where('ends_at >=', $now)
            ->orderBy('starts_at', 'ASC')
            ->findAll();
    }
}
