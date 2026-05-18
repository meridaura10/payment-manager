<?php

namespace Meridaura\PaymentManager\DTO\Recurring\Execute;

use Illuminate\Database\Eloquent\Model;

class RecurringExecuteRequest
{
    public function __construct(
        readonly public Model $payable,
        readonly public string|int $currency,
        readonly public float $amount,
        readonly public array $recurringData,
        readonly public null|string|array $webHookUrls = null,
        readonly public null|string|array $redirectUrls = null,
        readonly public array $driverData = [],
        readonly public array $paymentData = [],
    ) {
        //
    }
}