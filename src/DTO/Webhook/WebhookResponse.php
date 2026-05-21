<?php

namespace Meridaura\PaymentManager\DTO\Webhook;

use Meridaura\PaymentManager\DTO\PaymentError;
use Meridaura\PaymentManager\Models\Payment;

class WebhookResponse
{
    public function __construct(
        public readonly ?Payment $payment,
        public readonly ?WebhookParseData $parseData = null,
        public readonly ?PaymentError $error = null,
    ) {
        //
    }
}