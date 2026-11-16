<?php

namespace App\Libraries\Payments;

final class PaymentProblem extends \RuntimeException
{
    public function __construct(public readonly string $problemCode, string $message, public readonly int $httpStatus = 422)
    {
        parent::__construct($message);
    }
}
