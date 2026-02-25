<?php

namespace Meridaura\PaymentManager\Facades;

use Illuminate\Support\Facades\Facade;
use Meridaura\PaymentManager\Contracts\PaymentGatewayInterface;
use Meridaura\PaymentManager\Contracts\PaymentGatewaySubscriptionsInterface;

/**
 * @method static PaymentGatewayInterface|PaymentGatewaySubscriptionsInterface driver(string|null $driver = null)
 * @method static void extend(string $driver, \Closure $callback)
 *
 * * @see \Meridaura\PaymentManager\PaymentManager
 */


class PaymentManager extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'paymentManager';
    }
}