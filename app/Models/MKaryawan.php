<?php

namespace App\Models;

use CodeIgniter\Model;

class MKaryawan extends Model
{
    protected $table = 'tb_karyawan';
    protected $primaryKey = 'id_karyawan';
    protected $allowedFields = [
        'nama_karyawan', 'username', 'password', 
        'level', 'no_telp', 'gaji_pokok'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Untuk login
    public function getLogin($username, $password)
    {
        $builder = $this->db->table($this->table);
        $builder->where('username', $username);
        $query = $builder->get();
        $row = $query->getRow();
        
        if ($row) {
            if (password_verify($password, $row->password)) {
                return $row;
            }
        }
        return false;
    }
}