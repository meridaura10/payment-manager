<?php

namespace Meridaura\PaymentManager\Contracts;

use Meridaura\PaymentManager\Drivers\AbstractCharge;

interface SupportsChargesInterface extends PaymentGatewayInterface
{
    public function charges(): AbstractCharge;
}