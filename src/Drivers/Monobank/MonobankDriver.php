<?php

namespace Meridaura\PaymentManager\Drivers\Monobank;

use Meridaura\PaymentManager\Contracts\GatewayChargeInterface;
use Meridaura\PaymentManager\Contracts\GatewayRecurringInterface;
use Meridaura\PaymentManager\Contracts\SupportsChargesInterface;
use Meridaura\PaymentManager\Contracts\SupportsRecurringInterface;
use Meridaura\PaymentManager\Drivers\AbstractGateway;

class MonobankDriver extends AbstractGateway implements SupportsChargesInterface, SupportsRecurringInterface
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