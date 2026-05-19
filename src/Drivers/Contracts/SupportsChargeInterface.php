<?php

namespace Meridaura\PaymentManager\Drivers\Contracts;

use Meridaura\PaymentManager\Drivers\AbstractCharge;

interface SupportsChargeInterface
{
    public function charge(): AbstractCharge;
}