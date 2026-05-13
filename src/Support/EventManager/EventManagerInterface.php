<?php

namespace Meridaura\PaymentManager\Support\EventManager;

use Meridaura\PaymentManager\Enums\PaymentStageEnum;
use Meridaura\PaymentManager\Models\Payment;

interface EventManagerInterface
{
    public function dispatchLifecycleStage(Payment $payment, PaymentStageEnum $stage): void;

    public function dispatchChangeStatus(Payment $payment, string $newStatus, ?string $oldStatus = null): void;
}