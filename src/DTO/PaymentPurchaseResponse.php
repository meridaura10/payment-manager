<?php

namespace Meridaura\PaymentManager\DTO;

use Meridaura\PaymentManager\Enums\PaymentResponseStatusEnum;
use Meridaura\PaymentManager\Models\Payment;

class PaymentPurchaseResponse
{
     public function __construct(
        public readonly PaymentResponseStatusEnum   $status,
        public readonly Payment                     $payment,
        public readonly PaymentPurchaseRequest      $purchaseRequest,
        public readonly ?PaymentPurchaseApiResponse $gatewayResponse = null,
        public readonly ?Error                      $errors = null,
    ) {
        //
    }
}