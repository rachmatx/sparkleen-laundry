<?php

namespace App\Libraries\Mobile;

use CodeIgniter\HTTP\ResponseInterface;

final class ApiJson
{
    public static function error(ApiProblem $error): ResponseInterface
    {
        $body = ['code' => $error->apiCode, 'message' => $error->getMessage()];
        if ($error->fields !== []) {
            $body['fields'] = $error->fields;
        }
        return service('response')->setStatusCode($error->status)->setHeader('Cache-Control', 'no-store')->setJSON(['error' => $body]);
    }
}
