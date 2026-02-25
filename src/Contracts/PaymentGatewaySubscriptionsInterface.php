<?php

namespace Meridaura\PaymentManager\Contracts;

interface PaymentGatewaySubscriptionsInterface
{
    public function recurring(): GatewayRecurringInterface;
}