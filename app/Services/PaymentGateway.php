<?php
namespace App\Services;
use App\Libraries\Mobile\ApiProblem;

final class PaymentGateway
{
    public function request(string $method, string $suffix = '', ?array $body = null, ?string $requestKey = null): array
    {
        $url = env('PAYMENT_API_URL') ?: getenv('PAYMENT_API_URL');
        $key = env('PAYMENT_API_KEY') ?: getenv('PAYMENT_API_KEY');
        $secret = env('PAYMENT_WEBHOOK_SECRET') ?: getenv('PAYMENT_WEBHOOK_SECRET');
        $parts = is_string($url) ? parse_url($url) : false;
        if (!$parts || !in_array($parts['scheme'] ?? '', ['http', 'https'], true) || isset($parts['user']) || isset($parts['query']) || isset($parts['fragment'])
            || !$key || !$secret || hash_equals($key, $secret) || (ENVIRONMENT === 'production' && $parts['scheme'] !== 'https')) {
            throw new ApiProblem('PAYMENT_API_UNAVAILABLE', 'Pembayaran online belum tersedia. Hubungi outlet.', 502);
        }
        try {
            $options = ['headers' => ['X-Api-Key' => $key, 'Accept' => 'application/json'], 'timeout' => 5, 'connect_timeout' => 3, 'allow_redirects' => false, 'http_errors' => false];
            if ($requestKey !== null) $options['headers']['Idempotency-Key'] = $requestKey;
            if ($body !== null) $options['json'] = $body;
            $response = service('curlrequest')->request($method, rtrim($url, '/') . $suffix, $options);
            $raw = $response->getBody();
            $signature = $response->getHeaderLine('X-Webhook-Signature');
            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300 || strlen($raw) > 65536
                || !preg_match('/^[a-f0-9]{64}$/D', $signature) || !hash_equals(hash_hmac('sha256', $raw, $secret), $signature)) throw new \RuntimeException();
            $result = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
            if (($result['success'] ?? null) !== true || !is_array($result['data'] ?? null)) throw new \RuntimeException();
            return $result['data'];
        } catch (\Throwable $error) {
            // A timeout does not prove a create/cancel failed at the provider.
            throw new ApiProblem('PAYMENT_API_UNAVAILABLE', 'Status gateway belum dapat dipastikan. Coba periksa lagi; tagihan tidak dihapus.', 502);
        }
    }
}
