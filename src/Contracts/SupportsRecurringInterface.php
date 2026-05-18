<?php

namespace Meridaura\PaymentManager\Contracts;

use Meridaura\PaymentManager\Drivers\AbstractRecurring;

interface SupportsRecurringInterface
{
    public function recurring(): AbstractRecurring;
}