<?php

namespace App\Models;

use CodeIgniter\Model;

class SolqamCardModel extends Model
{
    protected $table         = 'solqam_cards';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'user_id',
        'card_code',
        'status',
        'issued_at',
        'pan',
        'last4',
        'cvv',
        'holder_name',
        'expires_at',
        'frozen_at',
        'pin_hash',
        'tier',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function forUser(int $userId): ?array
    {
        if ($userId < 1) {
            return null;
        }

        return $this->where('user_id', $userId)->first();
    }

    public function byCode(string $code): ?array
    {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return null;
        }

        return $this->where('card_code', $code)->first();
    }

    public function byPan(string $raw): ?array
    {
        $id = \App\Services\Card\SolqamCardNumber::normalize($raw);
        if ($id !== '') {
            $hit = $this->where('pan', $id)->first();
            if ($hit) {
                return $hit;
            }
            $hit = $this->where('pan', \App\Services\Card\SolqamCardNumber::display($id))->first();
            if ($hit) {
                return $hit;
            }
        }
        $compact = strtoupper(preg_replace('/[^A-Z0-9]/', '', $raw) ?? '');
        if ($compact !== '' && $compact !== $id) {
            $hit = $this->where('pan', $compact)->first();
            if ($hit) {
                return $hit;
            }
        }
        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        if (strlen($digits) === 16) {
            return $this->where('pan', $digits)->first();
        }

        return null;
    }
}
