<?php

namespace Meridaura\PaymentManager\DTO;

use Illuminate\Database\Eloquent\Model;

class PaymentPurchaseRequest
{
    public function __construct(
        readonly public Model $payable,
        readonly public string|int $currency,
        readonly public float $amount,
        readonly public ?string $webHookUrl = null,
        readonly public ?string $redirectUrl = null,
        readonly public array $driverData = [],
        readonly public array $paymentData = [],
    ) {}
}