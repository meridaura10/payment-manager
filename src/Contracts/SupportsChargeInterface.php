<?php

namespace Meridaura\PaymentManager\Contracts;

use Meridaura\PaymentManager\Drivers\AbstractCharge;

interface SupportsChargeInterface
{
    public function charge(): AbstractCharge;
}