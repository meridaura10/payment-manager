<?php

namespace Meridaura\PaymentManager\Drivers\Contracts;

use Meridaura\PaymentManager\Drivers\AbstractRecurring;

interface SupportsRecurringInterface
{
    public function recurring(): AbstractRecurring;
}