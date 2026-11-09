<?php

namespace App\Libraries\Mobile;

final class ApiProblem extends \RuntimeException
{
    public function __construct(public readonly string $apiCode, string $message, public readonly int $status = 422, public readonly array $fields = [])
    {
        parent::__construct($message);
    }
}
