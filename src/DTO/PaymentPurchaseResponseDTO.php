<?php

namespace Meridaura\PaymentManager\DTO;

class PaymentPurchaseResponseDTO
{
    public function __construct(
        readonly string $status,
        readonly array $system_data = [],
        readonly array $errors = [],
    )
    {

    }
}