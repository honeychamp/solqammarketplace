<?php

namespace App\Models;

use CodeIgniter\Model;

class ShipmentModel extends Model
{
    protected $table         = 'shipments';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'order_id',
        'seller_id',
        'fulfill_by',
        'handoff_status',
        'status',
        'tracking_number',
        'courier',
        'shipping_amount',
    ];
    protected $useTimestamps = true;

    public function forOrder(int $orderId): array
    {
        return $this->select('shipments.*, seller_profiles.store_name')
            ->join('seller_profiles', 'seller_profiles.user_id = shipments.seller_id', 'left')
            ->where('shipments.order_id', $orderId)
            ->findAll();
    }
}
