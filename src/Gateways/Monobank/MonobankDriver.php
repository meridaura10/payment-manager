<?php

namespace Meridaura\PaymentManager\Gateways\Monobank;

use Meridaura\PaymentManager\Contracts\GatewayChargeInterface;
use Meridaura\PaymentManager\Contracts\GatewayRecurringInterface;
use Meridaura\PaymentManager\Gateways\AbstractGateway;

class MonobankDriver extends AbstractGateway implements GatewayRecurringInterface
{
    public function charges(): GatewayChargeInterface
    {
        return new MonobankCharges($this->config);
    }

    public function recurring(): GatewayRecurringInterface
    {
        return new MonobankRecurring($this->config);
    }
}