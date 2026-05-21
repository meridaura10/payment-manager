<?php

namespace Meridaura\PaymentManager\Facades;

use Illuminate\Support\Facades\Facade;
use Meridaura\PaymentManager\Contracts\PaymentManagerInterface;

/**
 * @method static \Meridaura\PaymentManager\PaymentManager extend(string $driver, \Closure $callback)
 * @method static \Meridaura\PaymentManager\Drivers\AbstractCharge charge(?string $driver = null, array $config = [])
 * @method static \Meridaura\PaymentManager\Drivers\AbstractRecurring recurring(?string $driver = null, array $config = [])
 * @method static \Meridaura\PaymentManager\Drivers\AbstractWebhook webhook(?string $driver = null, array $config = [])
 * @method static \Meridaura\PaymentManager\Drivers\AbstractDriver driver(?string $driver = null, array $config = [])
 * @method static \Meridaura\PaymentManager\Drivers\AbstractDriver build(string $driver, array $config = [])
 * @method static bool existsDriver(string $driver, string $supportInterface = null)
 *
 * @see \Meridaura\PaymentManager\PaymentManager
 */

class PaymentManager extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PaymentManagerInterface::class;
    }
}