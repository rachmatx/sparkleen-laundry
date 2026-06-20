<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Models\MTransaksi;

class Webhook extends ResourceController
{
    use ResponseTrait;

    public function payment()
    {
        $payload = $this->request->getJSON(true);
        $signature = $this->request->getHeaderLine('X-Signature');
        $secret = getenv('PAYMENT_API_KEY');

        // Jika request bukan JSON atau signature kosong
        if (!$payload || !$signature) {
            return $this->failUnauthorized('Missing payload or signature');
        }

        $rawBody = $this->request->getBody();

        // Verifikasi HMAC menggunakan raw body
        $expectedSignature = hash_hmac('sha256', $rawBody, $secret);
        if (!hash_equals($expectedSignature, $signature)) {
            return $this->failUnauthorized('Invalid signature');
        }

        if ($payload['event'] === 'payment.success') {
            $transactionId = $payload['transaction_id'];

            $mTransaksi = new MTransaksi();
            $transaksi = $mTransaksi->where('payment_ref', $transactionId)->first();

            if ($transaksi) {
                // Update status_pembayaran menjadi 1 (Lunas)
                $mTransaksi->update($transaksi['id_transaksi'], [
                    'id_status_pembayaran' => 1
                ]);

                // Catat ke tb_pembayaran agar masuk ke riwayat
                $mPembayaran = new \App\Models\MPembayaran();
                
                // Hitung nominal karena bisa saja ini pelunasan bertahap (walau di protoype kita bayar full)
                $totalDibayar = $mPembayaran
                    ->selectSum('nominal_bayar', 'total')
                    ->where('id_transaksi', $transaksi['id_transaksi'])
                    ->first();
                $sudahDibayar = $totalDibayar['total'] ?? 0;
                $sisa         = (float) $transaksi['grand_total'] - $sudahDibayar;
                
                if ($sisa > 0) {
                    $mPembayaran->insert([
                        'id_transaksi'         => $transaksi['id_transaksi'],
                        'tgl_bayar'            => date('Y-m-d H:i:s'),
                        'nominal_bayar'        => $sisa, // Bayar full sisa tagihan
                        'sisa_tagihan'         => 0,
                        'id_metode_pembayaran' => $transaksi['id_metode_pembayaran'], // QRIS(2) / TF(3)
                    ]);
                }

                return $this->respond(['status' => 'success', 'message' => 'Payment confirmed']);
            } else {
                return $this->failNotFound('Transaction not found');
            }
        }

        return $this->respond(['status' => 'ignored']);
    }
}
