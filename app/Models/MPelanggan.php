<?php

namespace App\Models;

use CodeIgniter\Model;

class MPelanggan extends Model
{
    protected $table      = 'tb_pelanggan';
    protected $primaryKey = 'id_pelanggan';

    protected $allowedFields = [
        'kode_pelanggan', 'nama_pelanggan', 'no_telp',
        'alamat', 'poin', 'total_transaksi', 'is_aktif'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validasi bawaan model
    protected $validationRules = [
        'id_pelanggan'   => 'permit_empty',
        'kode_pelanggan' => 'required|is_unique[tb_pelanggan.kode_pelanggan,id_pelanggan,{id_pelanggan}]',
        'nama_pelanggan' => 'required|max_length[100]',
        'no_telp'        => 'required|min_length[9]|max_length[14]|numeric|regex_match[/^(08|62)/]|is_unique[tb_pelanggan.no_telp,id_pelanggan,{id_pelanggan}]',
        'alamat'         => 'required',
    ];

    protected $validationMessages = [
        'kode_pelanggan' => [
            'required'  => 'Kode pelanggan tidak boleh kosong.',
            'is_unique' => 'Kode pelanggan ini sudah terpakai (bentrok), silakan coba simpan ulang.',
        ],
        'no_telp' => [
            'required'    => 'Nomor telepon wajib diisi.',
            'min_length'  => 'Nomor telepon minimal 9 digit.',
            'max_length'  => 'Nomor telepon maksimal 14 digit.',
            'numeric'     => 'Nomor telepon hanya boleh berisi angka.',
            'regex_match' => 'Nomor telepon harus diawali dengan 08 atau 62.',
            'is_unique'   => 'Nomor telepon ini sudah terdaftar pada pelanggan lain.',
        ],
    ];

    // Aktifkan validasi saat insert & update
    protected $skipValidation = false;

    public function generateKode(): string
    {
        $builder = $this->db->table($this->table);
        $builder->select('kode_pelanggan');
        $builder->orderBy('LENGTH(kode_pelanggan)', 'DESC');
        $builder->orderBy('kode_pelanggan', 'DESC');
        $builder->limit(1);
        $row = $builder->get()->getRow();

        if ($row && $row->kode_pelanggan) {
            // Ambil angka dari kode terakhir (contoh: PLG0014 -> 14)
            $number = (int) preg_replace('/[^0-9]/', '', $row->kode_pelanggan);
            $nextNumber = $number + 1;
        } else {
            $nextNumber = 1;
        }

        return 'PLG' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}