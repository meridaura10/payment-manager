<?php

namespace Meridaura\PaymentManager;

use Illuminate\Support\Manager;
use Meridaura\PaymentManager\Contracts\PaymentManagerInterface;

class PaymentManager extends Manager implements PaymentManagerInterface
{

    public function getDefaultDriver()
    {
        return null;
    }
}