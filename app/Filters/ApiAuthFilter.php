<?php

namespace App\Filters;

use App\Libraries\Mobile\ApiJson;
use App\Libraries\Mobile\ApiProblem;
use App\Libraries\Mobile\CustomerAuth;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ApiAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        try {
            (new CustomerAuth())->fromHeader($request->getHeaderLine('Authorization'), true);
        } catch (ApiProblem $error) {
            return ApiJson::error($error);
        } catch (\Throwable $error) {
            return ApiJson::error(new ApiProblem('SERVER_ERROR', 'Terjadi kesalahan. Silakan coba lagi.', 500));
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
