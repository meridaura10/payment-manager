<?php

namespace Meridaura\PaymentManager\Contracts;

use Meridaura\PaymentManager\Drivers\AbstractWebhook;

interface SupportsWebhookInterface
{
    public function webhook(): AbstractWebhook;
}