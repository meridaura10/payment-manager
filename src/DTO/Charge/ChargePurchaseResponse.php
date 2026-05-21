<?php

namespace Meridaura\PaymentManager\DTO\Charge;

use Meridaura\PaymentManager\DTO\PaymentError;
use Meridaura\PaymentManager\Enums\PaymentResponseStatusEnum;
use Meridaura\PaymentManager\Models\Payment;

class ChargePurchaseResponse
{
    public function __construct(
        public readonly PaymentResponseStatusEnum $status,
        public readonly Payment $payment,
        public readonly ChargePurchaseRequest $request,
        public readonly ?ChargePurchaseParseResponse $response = null,
        public readonly ?PaymentError $errors = null,
        public readonly bool $isReused = false,
        public readonly ?string $message = null,
    ) {
        //
    }
}