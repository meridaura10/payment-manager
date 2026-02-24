<?php

namespace Meridaura\PaymentManager\Facades;

use Illuminate\Support\Facades\Facade;

///**
// * @method static PaymentGatewayInterface driver(string|null $driver = null)
// * @method static void extend(string $driver, \Closure $callback)
// * @method static string getDefaultDriver()
// * * @see \Meridaura\PaymentManager\PaymentManager
// */


class PaymentManager extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'paymentManager';
    }
}