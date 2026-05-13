<?php

namespace Meridaura\PaymentManager\Contracts;

use Meridaura\PaymentManager\Drivers\AbstractCharge;
use Meridaura\PaymentManager\Drivers\AbstractWebhook;
use Meridaura\PaymentManager\Support\PaymentRepository\PaymentRepositoryInterface;

interface PaymentManagerInterface
{
    public function extend(string $driver, \Closure $callback): static;

    public function driver(?string $driver = null, array $config = []): PaymentGatewayInterface;

    public function charges(?string $driver = null, array $config = []): AbstractCharge;

    public function recurring(?string $driver = null, array $config = []): GatewayRecurringInterface;

    public function webhooks(?string $driver = null, array $config = []): AbstractWebhook;

}