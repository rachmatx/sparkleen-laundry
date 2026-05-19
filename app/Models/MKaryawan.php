<?php

namespace App\Models;

use CodeIgniter\Model;

class MKaryawan extends Model
{
    protected $table      = 'tb_karyawan';
    protected $primaryKey = 'id_karyawan';

    protected $allowedFields = [
        'nama_karyawan', 'username', 'password',
        'level', 'no_telp', 'email', 'gaji_pokok',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validasi bawaan model
    protected $validationRules = [
        'nama_karyawan' => 'required|max_length[100]',
        'username'      => 'required|max_length[50]|is_unique[tb_karyawan.username,id_karyawan,{id_karyawan}]',
        'no_telp'       => 'required|max_length[20]|is_unique[tb_karyawan.no_telp,id_karyawan,{id_karyawan}]',
        'email'         => 'permit_empty|valid_email|max_length[100]|is_unique[tb_karyawan.email,id_karyawan,{id_karyawan}]',
        'level'         => 'required|in_list[kasir,admin,pemilik]',
    ];

    protected $validationMessages = [
        'username' => [
            'is_unique' => 'Username ini sudah digunakan oleh karyawan lain.',
        ],
        'no_telp' => [
            'is_unique' => 'Nomor telepon ini sudah terdaftar pada karyawan lain.',
        ],
        'email' => [
            'valid_email' => 'Format email tidak valid.',
            'is_unique'   => 'Email ini sudah digunakan oleh karyawan lain.',
        ],
    ];

    protected $skipValidation = false;

    /**
     * Login via username ATAU email
     */
    public function getLogin(string $identifier, string $password)
    {
        $builder = $this->db->table($this->table);

        if (str_contains($identifier, '@')) {
            $builder->where('email', $identifier);
        } else {
            $builder->where('username', $identifier);
        }

        $user = $builder->get()->getRow();

        if ($user && password_verify($password, $user->password)) {
            return $user;
        }

        return false;
    }
}