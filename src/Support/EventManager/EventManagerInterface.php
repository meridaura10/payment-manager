<?php

namespace Meridaura\PaymentManager\Support\EventManager;

use Meridaura\PaymentManager\Models\Payment;

interface EventManagerInterface
{
    public function dispatchLifecycleStage(Payment $payment, \UnitEnum|string $stage, \UnitEnum|string|null $operation = null): void;

    public function dispatchChangeStatus(Payment $payment, string $newStatus, ?string $oldStatus = null, \UnitEnum|string|null $operation = null): void;
}