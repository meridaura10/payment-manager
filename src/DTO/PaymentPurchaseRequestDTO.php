<?php

namespace Meridaura\PaymentManager\DTO;

class PaymentPurchaseRequestDTO
{
    public function __construct(
        readonly public string|int $orderId,
        readonly string|int $currency,
        readonly public float $amount,
        readonly ?string $webHookUrl = null,
        readonly ?string $redirectUrl = null,
        readonly ?string $description = null,
        readonly public array $driverOptions = [],
        readonly public array $headers = [],
    ) {}
}