<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Libraries\Mobile\ApiJson;
use App\Libraries\Mobile\ApiProblem;
use App\Libraries\Mobile\CustomerAuth;
use CodeIgniter\HTTP\ResponseInterface;

abstract class BaseApiController extends BaseController
{
    protected function handle(callable $action): ResponseInterface
    {
        try {
            return $action();
        } catch (ApiProblem $error) {
            return ApiJson::error($error);
        } catch (\Throwable $error) {
            // No request bodies, SQL, PINs or tokens in logs/responses.
            log_message('error', 'Customer API failure: {type}', ['type' => get_class($error)]);
            return ApiJson::error(new ApiProblem('SERVER_ERROR', 'Terjadi kesalahan. Silakan coba lagi.', 500));
        }
    }

    protected function ok(mixed $data, int $status = 200, ?array $meta = null): ResponseInterface
    {
        $body = ['data' => $data];
        if ($meta !== null) {
            $body['meta'] = $meta;
        }
        return $this->response->setStatusCode($status)->setHeader('Cache-Control', 'no-store')->setJSON($body);
    }

    protected function input(): array
    {
        try {
            $data = json_decode($this->request->getBody(), false, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new ApiProblem('INVALID_JSON', 'Body harus berupa JSON yang valid.', 400);
        }
        if (!$data instanceof \stdClass) {
            throw new ApiProblem('INVALID_JSON', 'Body harus berupa objek JSON.', 400);
        }
        return (array) $data;
    }

    protected function text(array $data, string $key, int $min, int $max): string
    {
        $value = $data[$key] ?? null;
        if (!is_string($value) || mb_strlen(trim($value)) < $min || mb_strlen(trim($value)) > $max) {
            throw new ApiProblem('VALIDATION_ERROR', 'Data yang dikirim belum valid.', 422, [$key => ["Panjang $key harus $min–$max karakter."]]);
        }
        return trim($value);
    }

    protected function pin(array $data): string
    {
        $pin = $data['pin'] ?? null;
        if (!is_string($pin) || !preg_match('/^[0-9]{6}$/D', $pin)) {
            throw new ApiProblem('VALIDATION_ERROR', 'PIN harus 6 digit angka.', 422, ['pin' => ['PIN harus 6 digit angka.']]);
        }
        return $pin;
    }

    protected function customer(): array
    {
        return (new CustomerAuth())->fromHeader($this->request->getHeaderLine('Authorization'));
    }
}
