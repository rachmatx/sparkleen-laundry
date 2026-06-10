<?php

namespace Tests\Support\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * TestDataSeeder — seeds all tables with known fixtures for testing.
 *
 * This seeder is run at the start of each database-dependent test
 * via `$this->seed(TestDataSeeder::class)`.
 */
class TestDataSeeder extends Seeder
{
    public function run()
    {
        // ── Karyawan (employees) ───────────────────────────────────
        $this->db->table('tb_karyawan')->insertBatch([
            [
                'id_karyawan'   => 1,
                'nama_karyawan' => 'Kasir Test',
                'username'      => 'kasir_test',
                'password'      => password_hash('password123', PASSWORD_DEFAULT),
                'level'         => 'kasir',
                'no_telp'       => '081200000001',
                'email'         => 'kasir@test.com',
                'gaji_pokok'    => 3000000,
                'is_aktif'      => 1,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],
            [
                'id_karyawan'   => 2,
                'nama_karyawan' => 'Admin Test',
                'username'      => 'admin_test',
                'password'      => password_hash('password123', PASSWORD_DEFAULT),
                'level'         => 'admin',
                'no_telp'       => '081200000002',
                'email'         => 'admin@test.com',
                'gaji_pokok'    => 4000000,
                'is_aktif'      => 1,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],
            [
                'id_karyawan'   => 3,
                'nama_karyawan' => 'Pemilik Test',
                'username'      => 'pemilik_test',
                'password'      => password_hash('password123', PASSWORD_DEFAULT),
                'level'         => 'pemilik',
                'no_telp'       => '081200000003',
                'email'         => 'pemilik@test.com',
                'gaji_pokok'    => 5000000,
                'is_aktif'      => 1,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],
            [
                'id_karyawan'   => 4,
                'nama_karyawan' => 'Nonaktif User',
                'username'      => 'nonaktif_test',
                'password'      => password_hash('password123', PASSWORD_DEFAULT),
                'level'         => 'kasir',
                'no_telp'       => '081200000004',
                'email'         => 'nonaktif@test.com',
                'gaji_pokok'    => 3000000,
                'is_aktif'      => 0,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],
        ]);

        // ── Pelanggan (customers) ──────────────────────────────────
        $this->db->table('tb_pelanggan')->insertBatch([
            [
                'id_pelanggan'   => 1,
                'kode_pelanggan' => 'PLG0001',
                'nama_pelanggan' => 'Budi Santoso',
                'no_telp'        => '081300000001',
                'alamat'         => 'Jl. Merdeka No. 1, Jakarta',
                'poin'           => 25,
                'total_transaksi'=> 150000,
                'is_aktif'       => 1,
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ],
            [
                'id_pelanggan'   => 2,
                'kode_pelanggan' => 'PLG0002',
                'nama_pelanggan' => 'Siti Aminah',
                'no_telp'        => '081300000002',
                'alamat'         => 'Jl. Sudirman No. 5, Bandung',
                'poin'           => 10,
                'total_transaksi'=> 75000,
                'is_aktif'       => 1,
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ],
        ]);

        // ── Layanan (services) ─────────────────────────────────────
        $this->db->table('tb_layanan')->insertBatch([
            [
                'id_layanan'    => 1,
                'nama_layanan'  => 'Cuci Reguler',
                'harga_per_kg'  => 7000,
                'estimasi_hari' => 3,
                'is_aktif'      => 1,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],
            [
                'id_layanan'    => 2,
                'nama_layanan'  => 'Cuci Ekspres',
                'harga_per_kg'  => 12000,
                'estimasi_hari' => 1,
                'is_aktif'      => 1,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],
            [
                'id_layanan'    => 3,
                'nama_layanan'  => 'Setrika Saja',
                'harga_per_kg'  => 5000,
                'estimasi_hari' => 2,
                'is_aktif'      => 0,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],
        ]);

        // ── Inventaris (inventory items) ───────────────────────────
        $this->db->table('tb_inventaris')->insertBatch([
            [
                'id_inventaris' => 1,
                'nama_barang'   => 'Deterjen Bubuk',
                'stok'          => 50.00,
                'satuan'        => 'kg',
                'harga_beli'    => 25000,
                'stok_minimal'  => 5.00,
                'is_aktif'      => 1,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],
            [
                'id_inventaris' => 2,
                'nama_barang'   => 'Pewangi',
                'stok'          => 20.00,
                'satuan'        => 'liter',
                'harga_beli'    => 15000,
                'stok_minimal'  => 3.00,
                'is_aktif'      => 1,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],
            [
                'id_inventaris' => 3,
                'nama_barang'   => 'Plastik Packing',
                'stok'          => 2.00,
                'satuan'        => 'pak',
                'harga_beli'    => 10000,
                'stok_minimal'  => 5.00,
                'is_aktif'      => 1,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],
        ]);

        // ── Layanan-Inventaris recipes ─────────────────────────────
        $this->db->table('tb_layanan_inventaris')->insertBatch([
            [
                'id_layanan_inventaris' => 1,
                'id_layanan'            => 1,
                'id_inventaris'         => 1,
                'jumlah_default'        => 0.05, // 0.05 kg per kg laundry
                'created_at'            => date('Y-m-d H:i:s'),
                'updated_at'            => date('Y-m-d H:i:s'),
            ],
            [
                'id_layanan_inventaris' => 2,
                'id_layanan'            => 1,
                'id_inventaris'         => 2,
                'jumlah_default'        => 0.02, // 0.02 liter per kg
                'created_at'            => date('Y-m-d H:i:s'),
                'updated_at'            => date('Y-m-d H:i:s'),
            ],
        ]);

        // ── Sample Transaksi ───────────────────────────────────────
        $this->db->table('tb_transaksi')->insert([
            'id_transaksi'         => 1,
            'no_nota'              => 'LAU/20260522/0001',
            'id_pelanggan'         => 1,
            'id_kasir'             => 1,
            'tgl_masuk'            => date('Y-m-d H:i:s'),
            'tgl_selesai_estimasi' => date('Y-m-d H:i:s', strtotime('+3 days')),
            'tgl_selesai_aktual'   => null,
            'total_berat'          => 5.0,
            'total_biaya'          => 35000,
            'diskon'               => 0,
            'poin_ditebus'         => 0,
            'poin_diperoleh'       => 3,
            'grand_total'          => 35000,
            'status'               => 'menunggu',
            'status_pembayaran'    => 'belum',
            'created_at'           => date('Y-m-d H:i:s'),
            'updated_at'           => date('Y-m-d H:i:s'),
        ]);

        // ── Detail Transaksi ───────────────────────────────────────
        $this->db->table('tb_detail_transaksi')->insert([
            'id_detail'     => 1,
            'id_transaksi'  => 1,
            'id_layanan'    => 1,
            'berat'         => 5.0,
            'harga_satuan'  => 7000,
            'subtotal'      => 35000,
        ]);

        // ── Log WhatsApp ───────────────────────────────────────────
        $this->db->table('tb_log_whatsapp')->insertBatch([
            [
                'id_log'            => 1,
                'no_nota'           => 'LAU/20260522/0001',
                'no_tujuan'         => '081300000001',
                'isi_pesan'         => 'Order baru telah diterima.',
                'tipe_notifikasi'   => 'order_baru',
                'provider'          => 'fonnte',
                'id_status_whatsapp'=> 2, // sukses
                'respon_api'        => '{"status":true}',
                'created_at'        => date('Y-m-d H:i:s'),
            ],
            [
                'id_log'            => 2,
                'no_nota'           => 'LAU/20260522/0001',
                'no_tujuan'         => '081300000001',
                'isi_pesan'         => 'Order gagal dikirim.',
                'tipe_notifikasi'   => 'order_baru',
                'provider'          => 'fonnte',
                'id_status_whatsapp'=> 3, // gagal
                'respon_api'        => '{"status":false}',
                'created_at'        => date('Y-m-d H:i:s', strtotime('-1 hour')),
            ],
        ]);

        // ── Log Aktivitas ──────────────────────────────────────────
        $this->db->table('tb_log_aktivitas')->insert([
            'id_log'          => 1,
            'id_karyawan'     => 1,
            'nama_karyawan'   => 'Kasir Test',
            'level_karyawan'  => 'kasir',
            'modul'           => 'Autentikasi',
            'aksi'            => 'Login',
            'id_referensi'    => 1,
            'nama_referensi'  => 'kasir_test',
            'detail'          => 'Berhasil login ke dalam sistem.',
            'ip_address'      => '127.0.0.1',
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
    }
}
