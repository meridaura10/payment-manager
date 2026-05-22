<?php

namespace Meridaura\PaymentManager\Drivers\Contracts;

use Meridaura\PaymentManager\Drivers\AbstractCash;

interface SupportsCashInterface
{
    public function cash(): AbstractCash;
}