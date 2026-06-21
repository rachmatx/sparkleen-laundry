<?php

namespace App\Services;

use App\Models\MTransaksi;
use App\Models\MDetailTransaksi;
use App\Models\MLayanan;
use App\Models\MInventaris;
use App\Models\MLayananInventaris;
use App\Models\MPembayaran;
use App\Models\MStatusLog;
use App\Models\MPelanggan;
use App\Models\MPenggunaanInventaris;
use App\Models\MasterStatusTransaksiModel;
use App\Models\MasterMetodePembayaranModel;
use Config\StatusTransaksi;
use Config\StatusPembayaran;

class TransaksiService
{
    protected $transaksiModel;
    protected $detailModel;
    protected $layananModel;
    protected $inventarisModel;
    protected $layananInventarisModel;
    protected $pembayaranModel;
    protected $statusLogModel;
    protected $pelangganModel;
    protected $penggunaanModel;

    public function __construct()
    {
        $this->transaksiModel         = new MTransaksi();
        $this->detailModel            = new MDetailTransaksi();
        $this->layananModel           = new MLayanan();
        $this->inventarisModel        = new MInventaris();
        $this->layananInventarisModel = new MLayananInventaris();
        $this->pembayaranModel        = new MPembayaran();
        $this->statusLogModel         = new MStatusLog();
        $this->pelangganModel         = new MPelanggan();
        $this->penggunaanModel        = new MPenggunaanInventaris();
    }

    /**
     * Hitung rekomendasi pemakaian inventaris berdasarkan isi keranjang sementara.
     *
     * @param array $temp Baris-baris temp cart (masing-masing punya id_layanan & berat)
     * @return array<int, float>  Key = id_inventaris, Value = total jumlah pakai
     */
    public function calculateCartRekomendasi(array $temp): array
    {
        if (empty($temp)) {
            return [];
        }

        // Batch fetch all recipes in one query
        $layananIds = array_unique(array_column($temp, 'id_layanan'));
        $allRecipes = $this->layananInventarisModel
            ->whereIn('id_layanan', $layananIds)
            ->findAll();

        // Group recipes by id_layanan
        $recipesByLayanan = [];
        foreach ($allRecipes as $r) {
            $recipesByLayanan[(int) $r['id_layanan']][] = $r;
        }

        $resepRekomendasi = [];
        foreach ($temp as $t) {
            $recipes = $recipesByLayanan[(int) $t['id_layanan']] ?? [];
            foreach ($recipes as $r) {
                $id_inv      = (int) $r['id_inventaris'];
                $jumlah_pakai = (float) $r['jumlah_default'] * (float) $t['berat'];
                if (isset($resepRekomendasi[$id_inv])) {
                    $resepRekomendasi[$id_inv] += $jumlah_pakai;
                } else {
                    $resepRekomendasi[$id_inv] = $jumlah_pakai;
                }
            }
        }

        return $resepRekomendasi;
    }

    /**
     * Validasi ketersediaan stok inventaris sebelum transaksi diproses.
     *
     * @param array $postData Data POST dari form (inv_id[], inv_jumlah_{id}, inv_ket_{id})
     * @return array{valid: bool, error_message: string}
     */
    public function validateStokInventaris(array $postData): array
    {
        $inventarisIds = $postData['inv_id'] ?? [];

        foreach ($inventarisIds as $id_inv) {
            $id_inv = (int) $id_inv;
            $jumlah = (float) ($postData['inv_jumlah_' . $id_inv] ?? 0);

            if ($id_inv > 0 && $jumlah > 0) {
                $invItem = $this->inventarisModel->find($id_inv);
                if ($invItem && $jumlah > (float) $invItem['stok']) {
                    return [
                        'valid'         => false,
                        'error_message' => 'Stok tidak mencukupi untuk bahan baku: ' . $invItem['nama_barang']
                            . ' (Stok saat ini: ' . number_format($invItem['stok'], 2, ',', '.') . ' ' . $invItem['satuan']
                            . ', dibutuhkan: ' . number_format($jumlah, 2, ',', '.') . ' ' . $invItem['satuan'] . ')',
                    ];
                }
            }
        }

        return ['valid' => true, 'error_message' => ''];
    }

    /**
     * Cek apakah transaksi memerlukan PIN pengambilan.
     * PIN wajib jika grand_total >= 100.000 ATAU salah satu layanan VIP.
     */
    public function checkPinRequired(int $id_transaksi, float $grand_total): bool
    {
        if ($grand_total >= 100000) {
            return true;
        }

        $details = $this->detailModel
            ->select('tb_layanan.nama_layanan')
            ->join('tb_layanan', 'tb_layanan.id_layanan = tb_detail_transaksi.id_layanan')
            ->where('id_transaksi', $id_transaksi)
            ->findAll();

        foreach ($details as $d) {
            if (stripos($d['nama_layanan'], 'vip') !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Proses perubahan status transaksi, termasuk validasi PIN dan pengiriman WA.
     *
     * @return array{success: bool, message: string, wa_result?: array}
     */
    public function prosesUbahStatus(int $id_transaksi, array $postData, string $sessionLevel): array
    {
        $id_status_baru    = $postData['id_status_transaksi'];
        $catatan           = $postData['catatan'] ?? '';
        $kirim_wa          = ($postData['kirim_wa'] ?? '') === '1';
        $input_pin         = $postData['pin_pengambilan'] ?? '';
        $id_karyawan       = (int) ($postData['id_karyawan'] ?? 0);

        $transaksi   = $this->transaksiModel->getTransaksiWithJoins($id_transaksi);
        $status_lama = $transaksi['status'];

        $statusModel      = new MasterStatusTransaksiModel();
        $status_baru_row  = $statusModel->find($id_status_baru);
        $status_baru      = $status_baru_row['nama_status'];

        // Validasi PIN jika status baru adalah 'diambil'
        if ($status_baru === 'diambil') {
            $is_pin_required = $this->checkPinRequired($id_transaksi, (float) $transaksi['grand_total']);

            if ($is_pin_required) {
                $db_pin    = $transaksi['pin_pengambilan'];
                $isPemilik = $sessionLevel === 'pemilik';

                if (!$isPemilik || !empty($input_pin)) {
                    if ($db_pin && $input_pin !== $db_pin) {
                        return ['success' => false, 'message' => 'PIN Pengambilan tidak cocok! Harap cek WhatsApp pelanggan.'];
                    }
                }
            }
        }

        // Update status di DB
        $this->transaksiModel->update($id_transaksi, ['id_status_transaksi' => $id_status_baru]);

        // Catat log perubahan status
        $this->statusLogModel->insert([
            'id_transaksi' => $id_transaksi,
            'status_lama'  => $status_lama,
            'status_baru'  => $status_baru,
            'tgl_ubah'     => date('Y-m-d H:i:s'),
            'id_karyawan'  => $id_karyawan,
            'catatan'      => $catatan,
        ]);

        $wa_result = null;

        if ($status_baru === 'selesai') {
            $this->transaksiModel->update($id_transaksi, ['tgl_selesai_aktual' => date('Y-m-d H:i:s')]);

            if ($status_lama !== 'selesai' && $kirim_wa) {
                $pelanggan = $this->pelangganModel->find($transaksi['id_pelanggan']);
                if ($pelanggan) {
                    $transaksiFresh = $this->transaksiModel->find($id_transaksi);
                    $wa_result      = service('whatsapp')->notifyOrderSelesai($transaksiFresh, $pelanggan);
                }
            }
        } elseif ($status_baru === 'diambil') {
            if ($status_lama !== 'diambil' && $kirim_wa) {
                $pelanggan = $this->pelangganModel->find($transaksi['id_pelanggan']);
                if ($pelanggan) {
                    $transaksiFresh = $this->transaksiModel->find($id_transaksi);
                    $wa_result      = service('whatsapp')->notifyOrderDiambil($transaksiFresh, $pelanggan);
                }
            }
        }

        return [
            'success'   => true,
            'message'   => 'Status berhasil diubah menjadi ' . ucfirst($status_baru),
            'wa_result' => $wa_result,
        ];
    }

    /**
     * Proses pembayaran transaksi.
     *
     * @return array{success: bool, message: string, kembalian?: float}
     */
    public function prosesPembayaran(int $id_transaksi, array $postData): array
    {
        $nominal_bayar        = (float) ($postData['nominal_bayar'] ?? 0);
        $id_metode_pembayaran = (int) ($postData['id_metode_pembayaran'] ?? 0);

        if ($nominal_bayar <= 0) {
            return ['success' => false, 'message' => 'Input tidak valid. Pastikan nominal lebih dari 0 dan metode bayar valid.'];
        }

        $transaksi    = $this->transaksiModel->find($id_transaksi);
        $totalDibayar = $this->pembayaranModel
            ->selectSum('nominal_bayar', 'total')
            ->where('id_transaksi', $id_transaksi)
            ->first();

        $sudahDibayar = $totalDibayar['total'] ?? 0;
        $sisa         = (float) $transaksi['grand_total'] - $sudahDibayar;

        if ($sisa <= 0) {
            return ['success' => false, 'message' => 'Tagihan transaksi ini sudah lunas.'];
        }

        $kembalian = 0;
        if ($nominal_bayar > $sisa) {
            $kembalian     = $nominal_bayar - $sisa;
            $nominal_bayar = $sisa;
        }

        // INTEGRASI PAYMENT API NODE.JS (Untuk QRIS dan Transfer)
        // 1 = Cash, 2 = QRIS, 3 = Transfer
        if ($id_metode_pembayaran === 2 || $id_metode_pembayaran === 3) {
            $methodName = $id_metode_pembayaran === 2 ? 'QRIS' : 'BANK_TRANSFER';
            
            $apiUrl = env('PAYMENT_API_URL') ?: getenv('PAYMENT_API_URL');
            $apiKey = env('PAYMENT_API_KEY') ?: getenv('PAYMENT_API_KEY');

            if ($apiUrl && $apiKey) {
                try {
                    $client = \Config\Services::curlrequest();
                    $response = $client->post($apiUrl, [
                        'headers' => [
                            'X-Api-Key'    => $apiKey,
                            'Accept'       => 'application/json',
                            'Content-Type' => 'application/json',
                        ],
                        'json' => [
                            'amount'       => $nominal_bayar,
                            'method'       => $methodName,
                            'customer_ref' => $transaksi['no_nota']
                        ],
                        'timeout' => 5
                    ]);

                    $resData = json_decode($response->getBody(), true);
                    if (isset($resData['success']) && $resData['success'] === true) {
                        // Update tb_transaksi dgn payment_ref dari API
                        $this->transaksiModel->update($id_transaksi, [
                            'payment_ref'    => $resData['data']['transaction_id'],
                            'payment_detail' => json_encode($resData['data']['payment_detail']),
                            'id_metode_pembayaran' => $id_metode_pembayaran,
                        ]);

                        return [
                            'success' => true, 
                            'message' => "Permintaan {$methodName} berhasil dikirim ke API. Menunggu pembayaran pelanggan/kasir via Node API."
                        ];
                    }
                } catch (\Exception $e) {
                    log_message('error', 'API Payment Gagal: ' . $e->getMessage());
                    // Fallback to normal behavior if API fails or unreachable
                }
            }
        }

        // Lanjut ke alur CASH atau Fallback jika API gagal
        $sisaSetelah = $sisa - $nominal_bayar;

        $db = db_connect();
        $db->transStart();

        $this->pembayaranModel->insert([
            'id_transaksi'         => $id_transaksi,
            'tgl_bayar'            => date('Y-m-d H:i:s'),
            'nominal_bayar'        => $nominal_bayar,
            'sisa_tagihan'         => $sisaSetelah,
            'id_metode_pembayaran' => $id_metode_pembayaran,
        ]);

        $statusPembayaran = ($sisaSetelah <= 0) ? StatusPembayaran::LUNAS : StatusPembayaran::CICIL;

        $this->transaksiModel->update($id_transaksi, [
            'id_status_pembayaran' => $statusPembayaran,
            'id_metode_pembayaran' => $id_metode_pembayaran,
        ]);

        $db->transComplete();

        if (!$db->transStatus()) {
            log_message('error', 'prosesPembayaran: DB transaction gagal untuk id_transaksi=' . $id_transaksi);
            return ['success' => false, 'message' => 'Gagal menyimpan pembayaran ke database. Silakan coba lagi.'];
        }

        $message = $kembalian > 0
            ? 'Pembayaran berhasil. Uang Kembalian: Rp ' . number_format($kembalian, 0, ',', '.')
            : 'Pembayaran berhasil disimpan.';

        return ['success' => true, 'message' => $message, 'kembalian' => $kembalian];
    }

    /**
     * Generate QR Code data URI untuk sisa tagihan pembayaran.
     *
     * @return string|null  Data URI SVG, atau null jika tidak ada sisa.
     */
    public function generateQrCode(float $sisa, string $no_nota): ?string
    {
        if ($sisa <= 0) {
            return null;
        }

        $qrData = "Pembayaran Nota: {$no_nota}\nNominal: Rp " . number_format($sisa, 0, ',', '.');

        try {
            $qrCode = new \Endroid\QrCode\QrCode(
                data: $qrData,
                encoding: new \Endroid\QrCode\Encoding\Encoding('UTF-8'),
                errorCorrectionLevel: \Endroid\QrCode\ErrorCorrectionLevel::High,
                size: 180,
                margin: 0,
                roundBlockSizeMode: \Endroid\QrCode\RoundBlockSizeMode::Margin
            );
            $writer = new \Endroid\QrCode\Writer\SvgWriter();
            $result = $writer->write($qrCode);
            return $result->getDataUri();
        } catch (\Exception $e) {
            log_message('error', 'Gagal generate QR Code: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Hitung sisa tagihan untuk sebuah transaksi.
     */
    public function hitungSisaTagihan(int $id_transaksi): float
    {
        $transaksi    = $this->transaksiModel->find($id_transaksi);
        $totalDibayar = $this->pembayaranModel
            ->selectSum('nominal_bayar', 'total')
            ->where('id_transaksi', $id_transaksi)
            ->first();

        $sudahDibayar = $totalDibayar['total'] ?? 0;
        return (float) $transaksi['grand_total'] - $sudahDibayar;
    }

    /**
     * Memproses logika transaksi baru, memotong stok, mengatur diskon poin, dsb.
     *
     * @param array $postData   Data POST dari form
     * @param int   $id_kasir   ID karyawan kasir
     * @param array $itemsTemp  Item keranjang sementara
     * @param array $pelanggan  Data pelanggan
     * @return array{success: bool, message?: string, id_transaksi?: int, pin_pengambilan?: string}
     */
    public function prosesTransaksiBaru(array $postData, int $id_kasir, array $itemsTemp, array $pelanggan): array
    {
        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            // Batch fetch all layanan in one query
            $layananIds = array_unique(array_column($itemsTemp, 'id_layanan'));
            $layananRows = $this->layananModel->whereIn('id_layanan', $layananIds)->findAll();
            $layananMap = [];
            foreach ($layananRows as $l) {
                $layananMap[(int) $l['id_layanan']] = $l;
            }

            // Hitung total
            $total_berat = 0;
            $total_biaya = 0;

            foreach ($itemsTemp as $item) {
                $layanan  = $layananMap[(int) $item['id_layanan']];
                $subtotal = $item['berat'] * $layanan['harga_per_kg'];
                $total_berat += $item['berat'];
                $total_biaya += $subtotal;
            }

            // Simpan transaksi
            $estimasiSelesai = $postData['tgl_selesai_estimasi'] ?? date('Y-m-d H:i:s', strtotime('+2 days'));

            // Perhitungan Poin & Diskon
            $poin_ditebus = (int)($postData['poin_ditebus'] ?? 0);
            $poin_tersedia = (int)($pelanggan['poin'] ?? 0);
            if ($poin_ditebus > $poin_tersedia) {
                $poin_ditebus = $poin_tersedia;
            }
            if ($poin_ditebus < 0) {
                $poin_ditebus = 0;
            }

            $diskon = $poin_ditebus * 300;
            if ($diskon > $total_biaya) {
                $diskon = $total_biaya;
                $poin_ditebus = (int)ceil($diskon / 300);
            }

            $grand_total = $total_biaya - $diskon;
            if ($grand_total < 0) {
                $grand_total = 0;
            }

            // Perolehan poin baru
            $poin_diperoleh = (int)floor($grand_total / 10000);

            $pin_plain = (string)random_int(1000, 9999);
            $dataTransaksi = [
                'id_pelanggan'         => $pelanggan['id_pelanggan'],
                'id_kasir'             => $id_kasir,
                'tgl_masuk'            => date('Y-m-d H:i:s'),
                'tgl_selesai_estimasi' => date('Y-m-d H:i:s', strtotime($estimasiSelesai)),
                'total_berat'          => $total_berat,
                'total_biaya'          => $total_biaya,
                'diskon'               => $diskon,
                'poin_ditebus'         => $poin_ditebus,
                'poin_diperoleh'       => $poin_diperoleh,
                'grand_total'          => $grand_total,
                'id_status_transaksi'  => StatusTransaksi::MENUNGGU,
                'id_status_pembayaran' => StatusPembayaran::BELUM,
                'pin_pengambilan'      => $pin_plain,
            ];

            $this->transaksiModel->save($dataTransaksi);
            $id_transaksi = $this->transaksiModel->insertID();

            $tanggal = date('Ymd');
            $no_nota = 'LAU/' . $tanggal . '/' . str_pad($id_transaksi, 4, '0', STR_PAD_LEFT);
            $this->transaksiModel->update($id_transaksi, ['no_nota' => $no_nota]);

            // Update poin pelanggan
            if ($pelanggan) {
                $poin_baru = $poin_tersedia - $poin_ditebus + $poin_diperoleh;
                $total_transaksi_baru = (float)($pelanggan['total_transaksi'] ?? 0) + $grand_total;
                $this->pelangganModel->update($pelanggan['id_pelanggan'], [
                    'poin'            => $poin_baru,
                    'total_transaksi' => $total_transaksi_baru
                ]);
            }

            // Simpan detail layanan (reuse layananMap - no extra queries)
            foreach ($itemsTemp as $item) {
                $layanan  = $layananMap[(int) $item['id_layanan']];
                $subtotal = $item['berat'] * $layanan['harga_per_kg'];

                $this->detailModel->save([
                    'id_transaksi' => $id_transaksi,
                    'id_layanan'   => $item['id_layanan'],
                    'berat'        => $item['berat'],
                    'harga_satuan' => $layanan['harga_per_kg'],
                    'subtotal'     => $subtotal,
                ]);
            }

            // Catat penggunaan inventaris & kurangi stok
            $inventarisIds = $postData['inv_id'] ?? [];
            foreach ($inventarisIds as $id_inv) {
                $id_inv = (int)$id_inv;
                $jumlah = (float)($postData['inv_jumlah_' . $id_inv] ?? 0);
                $ket    = $postData['inv_ket_' . $id_inv] ?? '';

                if ($id_inv > 0 && $jumlah > 0) {
                    $this->penggunaanModel->save([
                        'id_transaksi'  => $id_transaksi,
                        'id_inventaris' => $id_inv,
                        'jumlah_pakai'  => $jumlah,
                        'keterangan'    => $ket,
                    ]);
                    $this->inventarisModel->kurangiStok($id_inv, $jumlah);
                }
            }

            if ($db->transStatus() === false) {
                $db->transRollback();
                return ['success' => false, 'message' => 'Gagal memproses transaksi ke database.'];
            }

            $db->transCommit();
            return ['success' => true, 'id_transaksi' => $id_transaksi, 'pin_pengambilan' => $pin_plain];

        } catch (\Exception $e) {
            $db->transRollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
