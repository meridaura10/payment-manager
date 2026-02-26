<?php

namespace Meridaura\PaymentManager\DTO;

class PaymentErrorDTO
{
    public function __construct(
        readonly string $message,
        readonly int|string $code = 500,
        readonly array $validations = [],
        readonly ?int $httpStatus = null,
    ) {

    }
}