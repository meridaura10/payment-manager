<?php

namespace Meridaura\PaymentManager\Traits;

use Meridaura\PaymentManager\Support\Configurator\ConfiguratorInterface;
use Meridaura\PaymentManager\Support\EventManager\EventManagerInterface;
use Meridaura\PaymentManager\Support\PaymentRepository\PaymentRepositoryInterface;

trait UseCoreTrait
{
    protected function repository(): PaymentRepositoryInterface
    {
        return app(PaymentRepositoryInterface::class);
    }

    protected function configurator(): ConfiguratorInterface
    {
        return app(ConfiguratorInterface::class);
    }

    protected function events(): EventManagerInterface
    {
        return app(EventManagerInterface::class);
    }
}