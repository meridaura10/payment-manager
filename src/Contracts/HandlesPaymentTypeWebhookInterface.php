<?php

namespace Meridaura\PaymentManager\Contracts;

use Meridaura\PaymentManager\DTO\Webhook\WebhookParseData;
use Meridaura\PaymentManager\DTO\Webhook\WebhookResponse;
use Meridaura\PaymentManager\Models\Payment;

interface HandlesPaymentTypeWebhookInterface
{
    public function handleWebhook(Payment $payment, WebhookParseData $data): WebhookResponse;
}