<?php

namespace Meridaura\PaymentManager\Drivers\Contracts;

use Meridaura\PaymentManager\Drivers\AbstractWebhook;

interface SupportsWebhookInterface
{
    public function webhook(): AbstractWebhook;
}