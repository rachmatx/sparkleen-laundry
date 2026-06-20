<?php

namespace App\Controllers;

use App\Models\MTransaksi;

class PelangganBayar extends BaseController
{
    protected $transaksiModel;

    public function __construct()
    {
        $this->transaksiModel = new MTransaksi();
    }

    // Filter check session login
    private function checkLogin()
    {
        if (!session()->get('pelanggan_app_logged_in')) {
            return false;
        }
        return true;
    }

    public function index()
    {
        if ($this->checkLogin()) {
            return redirect()->to('/pelanggan-bayar/dashboard');
        }
        return view('pelanggan_app/login');
    }

    public function doLogin()
    {
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        if ($username === 'test' && $password === 'test123') {
            session()->set('pelanggan_app_logged_in', true);
            if (!session()->has('pelanggan_app_saldo')) {
                session()->set('pelanggan_app_saldo', 1000000); // 1 Juta default
            }
            if (!session()->has('pelanggan_app_history')) {
                session()->set('pelanggan_app_history', []);
            }
            return redirect()->to('/pelanggan-bayar/dashboard');
        }

        session()->setFlashdata('error', 'Username atau Password salah!');
        return redirect()->to('/pelanggan-bayar');
    }

    public function logout()
    {
        session()->remove('pelanggan_app_logged_in');
        return redirect()->to('/pelanggan-bayar');
    }

    public function dashboard()
    {
        if (!$this->checkLogin()) return redirect()->to('/pelanggan-bayar');
        
        $data['saldo'] = session()->get('pelanggan_app_saldo');
        return view('pelanggan_app/dashboard', $data);
    }

    public function scan()
    {
        if (!$this->checkLogin()) return redirect()->to('/pelanggan-bayar');
        return view('pelanggan_app/scan');
    }

    public function cekQr()
    {
        if (!$this->checkLogin()) return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized']);

        $qrText = $this->request->getPost('qr_text');
        // Format contoh: "Pembayaran Nota: LAU/20260620/0001\nNominal: Rp 15.000"
        
        preg_match('/Pembayaran Nota:\s*([^\n]+)/i', $qrText, $matches);
        if (isset($matches[1])) {
            $no_nota = trim($matches[1]);
            
            $transaksi = $this->transaksiModel->where('no_nota', $no_nota)
                              ->where('id_status_pembayaran !=', 1) // Bukan lunas
                              ->first();
            
            if ($transaksi && !empty($transaksi['payment_ref'])) {
                // Payment Ref dari Node API
                return $this->response->setJSON([
                    'success' => true,
                    'transaction_id' => $transaksi['payment_ref'],
                    'merchant_name' => 'Sparkleen Laundry',
                    'amount' => $transaksi['grand_total'],
                    'metode' => 'QRIS'
                ]);
            } else if ($transaksi) {
                 return $this->response->setJSON(['success' => false, 'message' => 'Kasir belum memproses Request QRIS di sistem.']);
            }
        }
        
        return $this->response->setJSON(['success' => false, 'message' => 'QR Code tidak valid atau transaksi sudah lunas.']);
    }

    public function transfer()
    {
        if (!$this->checkLogin()) return redirect()->to('/pelanggan-bayar');
        return view('pelanggan_app/transfer');
    }

    public function cekTransfer()
    {
        if (!$this->checkLogin()) return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized']);

        $rek = $this->request->getPost('rekening');
        $nominal = (float) $this->request->getPost('nominal');

        // Cari transaksi yang metodenya transfer (3), belum lunas, payment_ref tidak null
        $transaksis = $this->transaksiModel
                        ->where('id_metode_pembayaran', 3)
                        ->where('id_status_pembayaran !=', 1)
                        ->where('payment_ref IS NOT NULL')
                        ->orderBy('id_transaksi', 'DESC')
                        ->findAll();
        
        foreach ($transaksis as $t) {
            $pd = json_decode($t['payment_detail'], true);
            if (isset($pd['account_number']) && $pd['account_number'] == $rek && $t['grand_total'] == $nominal) {
                return $this->response->setJSON([
                    'success' => true,
                    'transaction_id' => $t['payment_ref'],
                    'merchant_name' => 'Sparkleen Laundry',
                    'amount' => $t['grand_total'],
                    'metode' => 'Transfer Bank'
                ]);
            }
        }

        return $this->response->setJSON(['success' => false, 'message' => 'Tagihan Transfer tidak ditemukan atau nominal tidak sesuai. Kasir sudah menekan tombol bayar?']);
    }

    public function konfirmasi()
    {
        if (!$this->checkLogin()) return redirect()->to('/pelanggan-bayar');
        
        $data['trx_id'] = $this->request->getGet('trx_id');
        $data['merchant'] = $this->request->getGet('merchant');
        $data['amount'] = $this->request->getGet('amount');
        
        if (!$data['trx_id']) {
            return redirect()->to('/pelanggan-bayar/dashboard');
        }

        return view('pelanggan_app/konfirmasi', $data);
    }

    public function prosesBayar()
    {
        if (!$this->checkLogin()) return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized']);

        $pin = $this->request->getPost('pin');
        $trx_id = $this->request->getPost('trx_id');
        $amount = (float) $this->request->getPost('amount');
        $saldo = session()->get('pelanggan_app_saldo');

        if ($pin !== '123456') {
            return $this->response->setJSON(['success' => false, 'message' => 'PIN salah!']);
        }

        if ($saldo < $amount) {
            return $this->response->setJSON(['success' => false, 'message' => 'Saldo tidak mencukupi!']);
        }

        // Tembak API Node.js confirm internal
        $apiUrl = getenv('PAYMENT_API_URL'); 
        // Ubah dari /api/transactions ke /api/transactions/:id/confirm
        // PAYMENT_API_URL = http://localhost:3000/api/transactions
        $confirmUrl = $apiUrl . '/' . $trx_id . '/confirm';
        $apiKey = getenv('PAYMENT_API_KEY');

        try {
            $client = \Config\Services::curlrequest();
            $response = $client->post($confirmUrl, [
                'headers' => [
                    'X-Api-Key'    => $apiKey,
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'timeout' => 5
            ]);

            $resData = json_decode($response->getBody(), true);
            if (isset($resData['success']) && $resData['success'] === true) {
                // Berhasil bayar, kurangi saldo
                session()->set('pelanggan_app_saldo', $saldo - $amount);
                
                // Catat ke riwayat
                $history = session()->get('pelanggan_app_history') ?? [];
                array_unshift($history, [
                    'id' => uniqid('trx_'),
                    'type' => 'OUT',
                    'title' => 'Pembayaran Laundry',
                    'amount' => $amount,
                    'date' => date('Y-m-d H:i:s'),
                    'merchant' => $this->request->getPost('merchant') ?? 'Sparkleen Laundry',
                ]);
                session()->set('pelanggan_app_history', $history);

                return $this->response->setJSON(['success' => true, 'message' => 'Pembayaran berhasil!']);
            } else {
                return $this->response->setJSON(['success' => false, 'message' => 'Pembayaran gagal di sistem.']);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => 'Terjadi kesalahan komunikasi ke API.']);
        }
    }

    public function topup()
    {
        if (!$this->checkLogin()) return redirect()->to('/pelanggan-bayar');
        return view('pelanggan_app/topup');
    }

    public function prosesTopup()
    {
        if (!$this->checkLogin()) return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized']);

        $amount = (float) $this->request->getPost('amount');
        if ($amount < 10000) {
            return $this->response->setJSON(['success' => false, 'message' => 'Minimal Top Up Rp 10.000']);
        }

        $saldo = session()->get('pelanggan_app_saldo') ?? 0;
        session()->set('pelanggan_app_saldo', $saldo + $amount);

        // Catat ke riwayat
        $history = session()->get('pelanggan_app_history') ?? [];
        array_unshift($history, [
            'id' => uniqid('top_'),
            'type' => 'IN',
            'title' => 'Top Up Saldo',
            'amount' => $amount,
            'date' => date('Y-m-d H:i:s'),
            'merchant' => 'Bank Transfer'
        ]);
        session()->set('pelanggan_app_history', $history);

        return $this->response->setJSON(['success' => true, 'message' => 'Top Up berhasil!']);
    }

    public function riwayat()
    {
        if (!$this->checkLogin()) return redirect()->to('/pelanggan-bayar');
        
        $data['history'] = session()->get('pelanggan_app_history') ?? [];
        return view('pelanggan_app/riwayat', $data);
    }
}
