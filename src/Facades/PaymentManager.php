<?php

namespace Meridaura\PaymentManager\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string getDefaultDriver()
 * @method static \Meridaura\PaymentManager\PaymentManager extend(string $driver, \Closure $callback)
 * @method static \Meridaura\PaymentManager\Contracts\GatewayChargeInterface charges(?string $driver = null, array $config = [])
 * @method static \Meridaura\PaymentManager\Contracts\GatewayRecurringInterface recurring(?string $driver = null, array $config = [])
 * @method static \Meridaura\PaymentManager\Contracts\PaymentGatewayInterface driver(?string $driver = null, array $config = [])
 * @method static \Meridaura\PaymentManager\Contracts\PaymentGatewayInterface build(string $driver, array $config = [])
 *
 * @see \Meridaura\PaymentManager\PaymentManager
 */

class PaymentManager extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'paymentManager';
    }
}