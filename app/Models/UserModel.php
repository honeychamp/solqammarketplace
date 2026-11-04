<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'name',
        'email',
        'phone',
        'password_hash',
        'role',
        'status',
        'is_verified',
        'api_token',
        'google_id',
        'facebook_id',
        'oauth_provider',
        'avatar_url',
    ];

    protected bool $allowEmptyInserts = false;

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected function initialize()
    {
        // Soft-deletes stay on. Skip per-request DESCRIBE.
    }

    // Validation
    protected $validationRules      = [
        'name'     => 'required|min_length[2]|max_length[150]',
        'email'    => 'required|valid_email|is_unique[users.email,id,{id}]',
        'phone'    => 'required|min_length[10]|max_length[20]|is_unique[users.phone,id,{id}]',
        'role'     => 'required|in_list[customer,seller,admin]',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    public function findByEmailOrPhone(string $login): ?array
    {
        $login = trim($login);
        $builder = $this->withDeleted(true)->groupStart()
            ->where('email', $login)
            ->orWhere('phone', $login);

        $digits = preg_replace('/[^0-9]/', '', $login);
        if (strlen($digits) >= 10) {
            $tenDigits = substr($digits, -10);
            $builder->orWhere('phone', '0' . $tenDigits)
                    ->orWhere('phone', $tenDigits)
                    ->orWhere('phone', '+92' . $tenDigits)
                    ->orWhere('phone', '92' . $tenDigits);
        }

        $user = $builder->groupEnd()->first();
        if (! $user || $this->isEffectivelyDeleted($user)) {
            return null;
        }

        return $user;
    }

    /**
     * Session checks must ignore MySQL zero-dates in deleted_at
     * (CI4 soft-delete WHERE deleted_at IS NULL would miss the admin row).
     */
    public function findAliveById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        try {
            $row = $this->db->table($this->table)->where('id', $id)->get()->getRowArray();
        } catch (\Throwable $e) {
            $row = $this->withDeleted(true)->find($id);
        }

        if (! $row || $this->isEffectivelyDeleted($row)) {
            return null;
        }

        return $row;
    }

    public function isEffectivelyDeleted(array $user): bool
    {
        $deleted = $user['deleted_at'] ?? null;
        if ($deleted === null || $deleted === '' || $deleted === '0000-00-00 00:00:00' || $deleted === '0000-00-00') {
            return false;
        }

        return true;
    }
}
