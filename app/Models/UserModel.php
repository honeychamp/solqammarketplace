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
        try {
            if (! $this->db->fieldExists('deleted_at', $this->table)) {
                $this->useSoftDeletes = false;
            }
        } catch (\Throwable $e) {
            $this->useSoftDeletes = false;
        }
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
        $builder = $this->groupStart()
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

        return $builder->groupEnd()->first();
    }
}
