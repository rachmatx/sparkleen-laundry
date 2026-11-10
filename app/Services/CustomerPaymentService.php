<?php
namespace App\Services;
use App\Libraries\Mobile\ApiProblem;
use App\Libraries\Payments\PaymentContract;

final class CustomerPaymentService
{
    public function enabled(): bool { return filter_var(env('MOBILE_PAYMENTS_ENABLED', false), FILTER_VALIDATE_BOOLEAN); }
    private function order(int $id, ?int $customer, bool $lock = false): array
    {
        $sql = 'SELECT * FROM tb_transaksi WHERE id_transaksi = ? AND deleted_at IS NULL';
        $params = [$id];
        if ($customer !== null) { $sql .= ' AND id_pelanggan = ?'; $params[] = $customer; }
        $row = db_connect()->query($sql . ($lock ? ' FOR UPDATE' : ''), $params)->getRowArray();
        if (!$row) throw new ApiProblem('NOT_FOUND', 'Transaksi tidak ditemukan.', 404);
        return $row;
    }
    private function active(int $id): ?array
    {
        return db_connect()->query("SELECT i.* FROM tb_payment_intent i JOIN tb_transaksi t ON t.id_transaksi = i.id_transaksi
            WHERE i.id_transaksi = ? AND i.status IN ('CREATING','PENDING') AND (i.active_order_id = ? OR i.gateway_ref = t.payment_ref)
            ORDER BY i.id_intent DESC LIMIT 1", [$id, $id])->getRowArray();
    }
    private function balance(array $order): array
    {
        $rows = db_connect()->query('SELECT nominal_bayar FROM tb_pembayaran WHERE id_transaksi = ? FOR UPDATE', [$order['id_transaksi']])->getResultArray();
        $paid = array_sum(array_map(static fn ($r) => PaymentContract::storedMoney($r['nominal_bayar']), $rows));
        return [(int) $paid, max(0, PaymentContract::storedMoney($order['grand_total']) - $paid)];
    }
    public function state(int $id, ?int $customer): array
    {
        return $this->atomic(function () use ($id, $customer) {
            $order = $this->order($id, $customer, true);
            [$paid, $remaining] = $this->balance($order);
            $intent = $this->active($id);
            $status = $remaining === 0 ? 'SUCCESS' : ($intent ? ($intent['status'] === 'CREATING' ? 'PROCESSING' : 'PENDING') : 'NONE');
            if ($remaining > 0 && $intent && $intent['status'] === 'PENDING' && $intent['expires_at'] && strtotime($intent['expires_at']) <= time()) $status = 'EXPIRED';
            if ($remaining > 0 && (($intent && $intent['mode'] !== 'SIMULATION') || (!$intent && $order['payment_ref'] && !db_connect()->table('tb_payment_intent')->where('gateway_ref', $order['payment_ref'])->where('status', 'APPLIED')->countAllResults()))) $status = 'REVIEW';
            // This prototype never exposes genuine configured bank/QR instruments.
            $public = [];
            if ($intent && $status === 'PENDING') {
                $public['simulation_code'] = $intent['gateway_ref'];
                if ($intent['method'] === 'QRIS') {
                    $public['qris_data'] = $intent['gateway_ref'];
                    $qr = new \Endroid\QrCode\QrCode(data: $intent['gateway_ref'], size: 240, margin: 12);
                    $public['qr_image'] = (new \Endroid\QrCode\Writer\PngWriter())->write($qr)->getDataUri();
                } else {
                    $public += ['bank_name' => 'BANK SIMULASI', 'account_number' => 'DEMO-NOT-A-BANK-ACCOUNT', 'account_holder' => 'Sparkleen (Simulasi)'];
                }
            }
            return ['status' => $status, 'enabled' => $this->enabled(), 'mode' => 'SIMULATION',
                'metode' => $intent ? ($intent['method'] === 'QRIS' ? 'QRIS' : 'TRANSFER') : null,
                'nominal' => $intent ? (int) $intent['amount'] : $remaining, 'total_dibayar' => $paid,
                'sisa' => $remaining, 'status_pembayaran' => $remaining === 0 ? 'lunas' : ($paid > 0 ? 'cicil' : 'belum'),
                'expired_at' => $intent['expires_at'] ?? null, 'payment_detail' => (object) $public];
        });
    }
    public function start(int $id, ?int $customer, string $method): array
    {
        $method = strtoupper(trim($method));
        if (!in_array($method, ['QRIS', 'TRANSFER'], true)) throw new ApiProblem('VALIDATION_ERROR', 'Pilih QRIS atau transfer.', 422, ['metode' => ['Metode tidak valid.']]);
        $method = $method === 'TRANSFER' ? 'BANK_TRANSFER' : 'QRIS';
        $attempt = $this->atomic(function ($db) use ($id, $customer, $method) {
            $order = $this->order($id, $customer, true);
            if (!$this->enabled()) throw new ApiProblem('PAYMENTS_DISABLED', 'Pembayaran online belum diaktifkan outlet.', 503);
            [, $remaining] = $this->balance($order);
            if (!$remaining) throw new ApiProblem('ALREADY_PAID', 'Tagihan sudah lunas.', 409);
            $active = $this->active($id);
            if ($active) {
                if ($active['method'] !== $method) throw new ApiProblem('CONFLICT_METHOD', 'Batalkan tagihan yang ada sebelum mengganti metode.', 409);
                return $active;
            }
            if ($order['payment_ref']) {
                $previous = $db->table('tb_payment_intent')->where('gateway_ref', $order['payment_ref'])->get()->getRowArray();
                if (!$previous || $previous['status'] !== 'APPLIED') throw new ApiProblem('PAYMENT_REVIEW_REQUIRED', 'Tagihan lama perlu diperiksa outlet.', 409);
            }
            $key = bin2hex(random_bytes(32));
            $db->table('tb_payment_intent')->insert(['gateway_ref' => null, 'merchant_id' => '', 'customer_ref' => $order['no_nota'],
                'id_transaksi' => $id, 'active_order_id' => $id, 'request_key' => $key, 'amount' => $remaining,
                'method' => $method, 'status' => 'CREATING', 'mode' => 'SIMULATION', 'created_at' => date('Y-m-d H:i:s')]);
            return $db->table('tb_payment_intent')->where('request_key', $key)->get()->getRowArray();
        });
        if ($attempt['status'] === 'CREATING') $this->resolve($attempt, $customer);
        return $this->state($id, $customer);
    }
    private function validateRemote(array $data, array $intent, bool $creating = false): void
    {
        foreach (['transaction_id', 'merchant_id', 'customer_ref', 'method'] as $field) PaymentContract::text($data, $field);
        if (PaymentContract::amount($data['amount'] ?? null) !== (int) $intent['amount'] || $data['customer_ref'] !== $intent['customer_ref']
            || $data['method'] !== $intent['method'] || ($data['mode'] ?? '') !== 'SIMULATION'
            || !in_array($data['status'] ?? '', ['PENDING', 'SUCCESS', 'CANCELLED', 'EXPIRED', 'FAILED'], true)
            || (!$creating && ($intent['gateway_ref'] !== $data['transaction_id'] || $intent['merchant_id'] !== $data['merchant_id']))
            || !is_string($data['expired_at'] ?? null) || strtotime($data['expired_at']) === false) {
            throw new ApiProblem('PAYMENT_CONTRACT_MISMATCH', 'Status gateway tidak cocok dengan tagihan.', 502);
        }
    }
    private function resolve(array $attempt, ?int $customer): void
    {
        $data = (new PaymentGateway())->request('POST', '', ['amount' => (int) $attempt['amount'], 'method' => $attempt['method'], 'customer_ref' => $attempt['customer_ref']], $attempt['request_key']);
        $this->validateRemote($data, $attempt, true);
        $this->atomic(function ($db) use ($attempt, $customer, $data) {
            $this->order((int) $attempt['id_transaksi'], $customer, true);
            $current = $db->query('SELECT * FROM tb_payment_intent WHERE id_intent = ? FOR UPDATE', [$attempt['id_intent']])->getRowArray();
            if ($current['status'] !== 'CREATING') return;
            $db->table('tb_payment_intent')->where('id_intent', $attempt['id_intent'])->update([
                'gateway_ref' => $data['transaction_id'], 'merchant_id' => $data['merchant_id'], 'expires_at' => $data['expired_at'],
                'payment_detail' => json_encode($data['payment_detail'] ?? []), 'status' => 'PENDING']);
            $db->table('tb_transaksi')->where('id_transaksi', $attempt['id_transaksi'])->update([
                'payment_ref' => $data['transaction_id'], 'payment_detail' => json_encode($data['payment_detail'] ?? []),
                'id_metode_pembayaran' => PaymentContract::METHODS[$attempt['method']]]);
        });
        // A replayed create may refer to a payment completed while its first response was lost.
        $bound = db_connect()->table('tb_payment_intent')->where('id_intent', $attempt['id_intent'])->get()->getRowArray();
        $this->remoteResult($bound, $data, $customer);
    }
    public function sync(int $id, ?int $customer, bool $cancel = false): array
    {
        $this->order($id, $customer);
        $intent = $this->active($id);
        if (!$intent) return $this->state($id, $customer);
        if ($intent['status'] === 'CREATING') {
            $this->resolve($intent, $customer);
            $intent = $this->active($id);
            if (!$intent) return $this->state($id, $customer);
        }
        $data = (new PaymentGateway())->request($cancel ? 'POST' : 'GET', '/' . rawurlencode($intent['gateway_ref']) . ($cancel ? '/cancel' : '/status'), $cancel ? [] : null);
        $this->validateRemote($data, $intent);
        $this->remoteResult($intent, $data, $customer);
        return $this->state($id, $customer);
    }
    private function remoteResult(array $intent, array $data, ?int $customer): void
    {
        if ($data['status'] === 'SUCCESS') {
            (new PaymentSettlementService())->apply(['event' => 'payment.success'] + $data);
        } elseif (in_array($data['status'], ['CANCELLED', 'EXPIRED', 'FAILED'], true)) {
            $this->atomic(function ($db) use ($intent, $data, $customer) {
                $order = $this->order((int) $intent['id_transaksi'], $customer, true);
                $current = $db->query('SELECT * FROM tb_payment_intent WHERE id_intent = ? FOR UPDATE', [$intent['id_intent']])->getRowArray();
                if ($current['status'] === 'APPLIED') return;
                $db->table('tb_payment_intent')->where('id_intent', $intent['id_intent'])->update(['status' => $data['status'], 'active_order_id' => null]);
                if ($order['payment_ref'] === $intent['gateway_ref']) $db->table('tb_transaksi')->where('id_transaksi', $order['id_transaksi'])->update(['payment_ref' => null, 'payment_detail' => null]);
            });
        }
    }
    private function atomic(callable $action): mixed
    {
        $db = db_connect();
        if (!$db->transBegin()) throw new \RuntimeException('Payment transaction unavailable.');
        try { $result = $action($db); if (!$db->transStatus() || !$db->transCommit()) throw new \RuntimeException('Payment commit failed.'); return $result; }
        catch (\Throwable $error) { $db->transRollback(); $db->resetTransStatus(); throw $error; }
    }
}
