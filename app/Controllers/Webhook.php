<?php

namespace App\Controllers;

use App\Libraries\Payments\PaymentProblem;
use App\Services\PaymentSettlementService;

class Webhook extends BaseController
{
    public function payment()
    {
        try {
            $secret = env('PAYMENT_WEBHOOK_SECRET') ?: getenv('PAYMENT_WEBHOOK_SECRET');
            $merchantKey = env('PAYMENT_API_KEY') ?: getenv('PAYMENT_API_KEY');
            if (!is_string($secret) || trim($secret) === '' || (is_string($merchantKey) && hash_equals($secret, $merchantKey))) {
                throw new PaymentProblem('WEBHOOK_NOT_CONFIGURED', 'Webhook pembayaran belum dikonfigurasi.', 503);
            }
            $raw = $this->request->getBody();
            if (strlen($raw) > 65536) {
                throw new PaymentProblem('PAYLOAD_TOO_LARGE', 'Payload terlalu besar.', 413);
            }
            $signature = $this->request->getHeaderLine('X-Signature');
            if (!preg_match('/^[a-f0-9]{64}$/D', $signature) || !hash_equals(hash_hmac('sha256', $raw, $secret), $signature)) {
                throw new PaymentProblem('INVALID_SIGNATURE', 'Signature tidak valid.', 401);
            }
            try {
                $payload = json_decode($raw, false, 32, JSON_THROW_ON_ERROR);
            } catch (\JsonException $error) {
                throw new PaymentProblem('INVALID_JSON', 'JSON tidak valid.', 400);
            }
            if (!$payload instanceof \stdClass || !is_string($payload->event ?? null)) {
                throw new PaymentProblem('INVALID_PAYLOAD', 'Payload event tidak valid.', 422);
            }
            if ($payload->event !== 'payment.success') {
                return $this->json(['status' => 'ignored']);
            }
            $duplicate = (new PaymentSettlementService())->apply((array) $payload);
            return $this->json(['status' => 'success', 'duplicate' => $duplicate]);
        } catch (PaymentProblem $error) {
            return $this->json(['status' => 'error', 'code' => $error->problemCode, 'message' => $error->getMessage()], $error->httpStatus);
        } catch (\Throwable $error) {
            // Never log request bodies, expected HMACs, signatures or gateway keys.
            log_message('error', 'Payment webhook failure: {type}', ['type' => get_class($error)]);
            return $this->json(['status' => 'error', 'code' => 'SERVER_ERROR', 'message' => 'Pembayaran belum dapat dikonfirmasi.'], 500);
        }
    }

    private function json(array $body, int $status = 200)
    {
        return $this->response->setStatusCode($status)->setHeader('Cache-Control', 'no-store')->setJSON($body);
    }
}
