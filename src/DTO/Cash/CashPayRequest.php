<?php

namespace Meridaura\PaymentManager\DTO\Cash;

use Illuminate\Database\Eloquent\Model;

class CashPayRequest
{
    public function __construct(
        readonly public Model $payable,
        readonly public string|int $currency,
        readonly public float $amount,
        public readonly \UnitEnum|string $type,
        public readonly \UnitEnum|string $gateway,
        readonly public array $driverData = [],
        readonly public array $paymentData = [],
    ) {

    }
}