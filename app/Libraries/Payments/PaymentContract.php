<?php

namespace App\Libraries\Payments;

final class PaymentContract
{
    // Existing Laundry DECIMAL(10,2) columns; this integration uses whole rupiah.
    public const MAX_RUPIAH = 99999999;
    public const METHODS = ['QRIS' => 2, 'BANK_TRANSFER' => 3];

    public static function text(array $data, string $field): string
    {
        $value = $data[$field] ?? null;
        if (!is_string($value) || $value === '' || strlen($value) > 100 || trim($value) !== $value) {
            throw new PaymentProblem('INVALID_PAYLOAD', 'Field pembayaran tidak valid.');
        }
        return $value;
    }

    public static function amount(mixed $value): int
    {
        if (!is_int($value) || $value <= 0 || $value > self::MAX_RUPIAH) {
            throw new PaymentProblem('INVALID_AMOUNT', 'Nominal harus berupa integer rupiah positif yang didukung.');
        }
        return $value;
    }

    public static function storedMoney(mixed $value): int
    {
        $string = (string) $value;
        if (!preg_match('/^[0-9]+(?:\.0{1,2})?$/D', $string) || (float) $string > self::MAX_RUPIAH) {
            throw new PaymentProblem('BALANCE_CONFLICT', 'Nominal transaksi memerlukan pemeriksaan kasir.', 409);
        }
        return (int) $string;
    }

    public static function success(array $payload): array
    {
        foreach (['transaction_id', 'merchant_id', 'customer_ref', 'method'] as $field) {
            self::text($payload, $field);
        }
        self::amount($payload['amount'] ?? null);
        if (($payload['status'] ?? null) !== 'SUCCESS' || !isset(self::METHODS[$payload['method']])) {
            throw new PaymentProblem('INVALID_PAYLOAD', 'Status atau metode pembayaran tidak valid.');
        }
        $date = $payload['paid_at'] ?? null;
        if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-]\d{2}:\d{2})$/D', $date)) {
            throw new PaymentProblem('INVALID_PAYLOAD', 'Waktu pembayaran harus ISO-8601 dengan zona.');
        }
        try {
            $parsed = new \DateTimeImmutable($date);
            $errors = \DateTimeImmutable::getLastErrors();
            if ($errors !== false && ($errors['warning_count'] || $errors['error_count'])) {
                throw new \RuntimeException();
            }
        } catch (\Throwable $error) {
            throw new PaymentProblem('INVALID_PAYLOAD', 'Waktu pembayaran tidak valid.');
        }
        $payload['payment_time'] = $parsed->setTimezone(new \DateTimeZone('Asia/Jakarta'))->format('Y-m-d H:i:s');
        return $payload;
    }
}
