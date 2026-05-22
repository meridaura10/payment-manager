<?php

namespace Meridaura\PaymentManager\DTO\Cash;

use Meridaura\PaymentManager\Models\Payment;

class CashPayResponse
{
    public function __construct(
        public readonly CashPayRequest $request,
        public readonly Payment $payment,
    ) {

    }
}