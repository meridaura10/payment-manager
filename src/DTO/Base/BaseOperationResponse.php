<?php

namespace Meridaura\PaymentManager\DTO\Base;
use Meridaura\PaymentManager\DTO\Error;
use Meridaura\PaymentManager\Enums\PaymentResponseStatusEnum;
use Meridaura\PaymentManager\Models\Payment;

class BaseOperationResponse
{
    public function __construct(
        public readonly PaymentResponseStatusEnum    $status,
        public readonly Payment                      $payment,
        public readonly BaseOperationRequest         $request,
        public readonly ?BaseParseResponse           $response = null,
        public readonly ?Error                       $errors = null,
        public readonly bool                         $isReused = false,
    ) {
        //
    }
}