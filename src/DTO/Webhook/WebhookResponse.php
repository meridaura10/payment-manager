<?php

namespace Meridaura\PaymentManager\DTO\Webhook;

use Meridaura\PaymentManager\DTO\Error;
use Meridaura\PaymentManager\Models\Payment;

class WebhookResponse
{
    public function __construct(
        public readonly ?Payment $payment,
        public readonly ?WebhookParseData $parseData = null,
        public readonly ?Error $error = null,
    ) {
        //
    }
}