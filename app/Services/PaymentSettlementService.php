<?php

namespace App\Services;

use App\Libraries\Payments\PaymentContract;
use App\Libraries\Payments\PaymentProblem;
use Config\StatusPembayaran;

final class PaymentSettlementService
{
    /** Called only within the cashier's locked transaction, after a gateway response. */
    public function recordIntent(array $order, array $data, int $amount, string $method): void
    {
        foreach (['transaction_id', 'merchant_id', 'customer_ref'] as $field) {
            PaymentContract::text($data, $field);
        }
        if (PaymentContract::amount($data['amount'] ?? null) !== $amount || ($data['method'] ?? null) !== $method
            || $data['customer_ref'] !== $order['no_nota'] || ($data['status'] ?? null) !== 'PENDING') {
            throw new PaymentProblem('GATEWAY_CONTRACT_MISMATCH', 'Respons gateway tidak cocok dengan tagihan.', 502);
        }
        if (!db_connect()->table('tb_payment_intent')->insert([
            'gateway_ref' => $data['transaction_id'], 'merchant_id' => $data['merchant_id'],
            'customer_ref' => $data['customer_ref'], 'id_transaksi' => $order['id_transaksi'],
            'amount' => $amount, 'method' => $method, 'active_order_id' => $order['id_transaksi'],
            'expires_at' => $data['expired_at'] ?? null, 'payment_detail' => json_encode($data['payment_detail'] ?? []),
            'mode' => $data['mode'] ?? null, 'created_at' => date('Y-m-d H:i:s'),
        ])) {
            throw new \RuntimeException('Unable to persist payment intent.');
        }
    }

    public function apply(array $payload): bool
    {
        $payload = PaymentContract::success($payload);
        $db = db_connect();
        // Resolve the order before opening the transaction; locking reads below
        // recheck the intent and always acquire the order lock first, like cash.
        $lookup = $db->table('tb_payment_intent')->where('gateway_ref', $payload['transaction_id'])->get()->getRowArray();
        if (!$lookup) {
            throw new PaymentProblem('INTENT_NOT_FOUND', 'Tagihan pembayaran tidak ditemukan.', 404);
        }
        if (!$db->transBegin()) {
            throw new \RuntimeException('Unable to begin payment transaction.');
        }
        try {
            $order = $db->query('SELECT * FROM tb_transaksi WHERE id_transaksi = ? AND deleted_at IS NULL FOR UPDATE', [$lookup['id_transaksi']])->getRowArray();
            $intent = $db->query('SELECT * FROM tb_payment_intent WHERE id_intent = ? FOR UPDATE', [$lookup['id_intent']])->getRowArray();
            if (!$order || !$intent) {
                throw new PaymentProblem('INTENT_NOT_FOUND', 'Tagihan pembayaran tidak ditemukan.', 404);
            }
            foreach (['gateway_ref' => 'transaction_id', 'merchant_id' => 'merchant_id', 'customer_ref' => 'customer_ref', 'method' => 'method'] as $stored => $field) {
                if (!hash_equals($intent[$stored], $payload[$field])) {
                    throw new PaymentProblem('INTENT_MISMATCH', 'Callback tidak cocok dengan tagihan.', 409);
                }
            }
            if ((int) $intent['amount'] !== $payload['amount']) {
                throw new PaymentProblem('INTENT_MISMATCH', 'Nominal callback tidak cocok dengan tagihan.', 409);
            }
            $duplicate = $intent['status'] === 'APPLIED';
            if (!$duplicate) {
                if ($intent['status'] !== 'PENDING' || $order['payment_ref'] !== $intent['gateway_ref']
                    || $order['no_nota'] !== $intent['customer_ref']
                    || (int) $order['id_metode_pembayaran'] !== PaymentContract::METHODS[$intent['method']]) {
                    throw new PaymentProblem('STALE_INTENT', 'Tagihan telah berubah; perlu pemeriksaan kasir.', 409);
                }
                // Lock the ledger rows as well; do not read a stale repeatable-read snapshot.
                $payments = $db->query('SELECT nominal_bayar FROM tb_pembayaran WHERE id_transaksi = ? FOR UPDATE', [$order['id_transaksi']])->getResultArray();
                $paid = array_sum(array_map(static fn ($row) => PaymentContract::storedMoney($row['nominal_bayar']), $payments));
                $remaining = PaymentContract::storedMoney($order['grand_total']) - $paid;
                if ($remaining < $payload['amount']) {
                    throw new PaymentProblem('BALANCE_CONFLICT', 'Saldo tagihan berubah; perlu pemeriksaan kasir.', 409);
                }
                $remaining -= $payload['amount'];
                if (!$db->table('tb_pembayaran')->insert([
                    'id_transaksi' => $order['id_transaksi'], 'tgl_bayar' => $payload['payment_time'],
                    'nominal_bayar' => $payload['amount'], 'sisa_tagihan' => $remaining,
                    'id_metode_pembayaran' => PaymentContract::METHODS[$intent['method']],
                ])) {
                    throw new \RuntimeException('Unable to insert payment.');
                }
                $paymentId = $db->insertID();
                (new CustomerNotificationService())->publish((int)$order['id_pelanggan'], 'payment:'.$paymentId, 'payment',
                    $remaining === 0 ? 'Pembayaran lunas' : 'Pembayaran diterima', 'Pembayaran online terkonfirmasi pada nota '.$order['no_nota'].'.', 'order', (int)$order['id_transaksi']);
                if (!$db->table('tb_transaksi')->where('id_transaksi', $order['id_transaksi'])->update([
                    'id_status_pembayaran' => $remaining === 0 ? StatusPembayaran::LUNAS : StatusPembayaran::CICIL,
                ]) || !$db->table('tb_payment_intent')->where('id_intent', $intent['id_intent'])->update([
                    'status' => 'APPLIED', 'active_order_id' => null, 'id_pembayaran' => $paymentId, 'applied_at' => date('Y-m-d H:i:s'),
                ])) {
                    throw new \RuntimeException('Unable to complete payment.');
                }
            }
            if (!$db->transStatus() || !$db->transCommit()) {
                throw new \RuntimeException('Payment commit failed.');
            }
            return $duplicate;
        } catch (\Throwable $error) {
            $db->transRollback();
            $db->resetTransStatus();
            throw $error;
        }
    }
}
