<?php

namespace App\Models;

use CodeIgniter\Model;

class MarketingEventLogModel extends Model
{
    protected $table         = 'marketing_event_logs';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'event_id',
        'platform',
        'event_name',
        'payload',
        'response',
        'http_code',
        'status',
        'created_at',
    ];

    public function record(
        string $platform,
        string $eventName,
        string $eventId,
        $payload,
        $response,
        string $status,
        ?int $httpCode = null
    ): void {
        try {
            $this->insert([
                'event_id'   => substr($eventId, 0, 80),
                'platform'   => substr($platform, 0, 30),
                'event_name' => substr($eventName, 0, 80),
                'payload'    => is_string($payload) ? $payload : json_encode($payload),
                'response'   => is_string($response) ? $response : json_encode($response),
                'http_code'  => $httpCode,
                'status'     => $status,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Marketing log: ' . $e->getMessage());
        }
    }
}
