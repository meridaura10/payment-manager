<?php

namespace Meridaura\PaymentManager\DTO\Recurring\Execute;

use Meridaura\PaymentManager\DTO\Error;
use Meridaura\PaymentManager\Enums\PaymentResponseStatusEnum;
use Meridaura\PaymentManager\Models\Payment;

class RecurringExecuteResponse
{
    public function __construct(
        public readonly PaymentResponseStatusEnum    $status,
        public readonly Payment                      $payment,
        public readonly RecurringExecuteRequest      $request,
        public readonly ?RecurringExecuteParseResponse $response = null,
        public readonly ?Error                       $errors = null,
    ) {
        //
    }
}