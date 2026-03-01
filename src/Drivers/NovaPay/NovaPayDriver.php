<?php

namespace Meridaura\PaymentManager\Drivers\NovaPay;

use Meridaura\PaymentManager\Contracts\GatewayChargeInterface;
use Meridaura\PaymentManager\Contracts\SupportsChargesInterface;
use Meridaura\PaymentManager\Drivers\AbstractGateway;

class NovaPayDriver extends AbstractGateway implements SupportsChargesInterface
{
    public function charges(): GatewayChargeInterface
    {
        return new NovaPayCharges($this->config);
    }
}