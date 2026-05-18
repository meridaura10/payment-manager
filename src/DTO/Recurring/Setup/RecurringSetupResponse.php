<?php

namespace Meridaura\PaymentManager\DTO\Recurring\Setup;

use Meridaura\PaymentManager\DTO\Error;
use Meridaura\PaymentManager\Enums\PaymentResponseStatusEnum;
use Meridaura\PaymentManager\Models\Payment;

class RecurringSetupResponse
{
     public function __construct(
        public readonly PaymentResponseStatusEnum    $status,
        public readonly Payment                      $payment,
        public readonly RecurringSetupRequest        $request,
        public readonly ?RecurringSetupParseResponse $response = null,
        public readonly ?Error                       $errors = null,
        public readonly bool                         $isReused = false,
    ) {
        //
    }
}