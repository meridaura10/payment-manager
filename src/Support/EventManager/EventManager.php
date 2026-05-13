<?php

namespace Meridaura\PaymentManager\Support\EventManager;

use Illuminate\Support\Facades\Log;
use Meridaura\PaymentManager\Enums\PaymentEventEnum;
use Meridaura\PaymentManager\Enums\PaymentStageEnum;
use Meridaura\PaymentManager\Enums\PaymentTypeEnum;
use Meridaura\PaymentManager\Models\Payment;
use Meridaura\PaymentManager\Support\Configurator\ConfiguratorInterface;
use Meridaura\PaymentManager\Support\PaymentRepository\PaymentRepositoryInterface;
use Throwable;

class EventManager implements EventManagerInterface
{
    public function __construct(
        protected ConfiguratorInterface $configurator,
        protected PaymentRepositoryInterface $repository,
    ) {
        //
    }

    public function dispatchLifecycleStage(Payment $payment, PaymentStageEnum $stage): void
    {
        if ($paymentType = $this->getPaymentType($payment)) {
            match ($stage) {
                PaymentStageEnum::CREATED  => $this->dispatchPaymentCreated($payment, $paymentType),
                PaymentStageEnum::PENDING  => $this->dispatchPaymentPending($payment, $paymentType),
                PaymentStageEnum::PAID     => $this->dispatchPaymentPaid($payment, $paymentType),
                PaymentStageEnum::FAILED   => $this->dispatchPaymentFailed($payment, $paymentType),
                PaymentStageEnum::CANCELED => $this->dispatchPaymentCanceled($payment, $paymentType),
            };
        }
    }

    public function dispatchChangeStatus(Payment $payment, string $newStatus, ?string $oldStatus = null): void
    {
        $generalEventClass = $this->configurator->getEventClass(PaymentEventEnum::STATUS_CHANGED->name);
        $this->dispatch($generalEventClass, $payment, $oldStatus, $newStatus);

        if ($paymentType = $this->getPaymentType($payment)) {
            $typeStatusChangedEventClass = $this->getEventTypeClass($paymentType, PaymentEventEnum::STATUS_CHANGED->name);
            $this->dispatch($typeStatusChangedEventClass, $payment, $oldStatus, $newStatus);
        }
    }

    protected function dispatchPaymentCreated(Payment $payment, PaymentTypeEnum $type): void
    {
        $eventClass = $this->getEventTypeClass($type, PaymentStageEnum::CREATED);
        $this->dispatch($eventClass, $payment);
    }

    protected function dispatchPaymentPending(Payment $payment, PaymentTypeEnum $type): void
    {
        $eventClass = $this->getEventTypeClass($type, PaymentStageEnum::PENDING);
        $this->dispatch($eventClass, $payment);
    }

    protected function dispatchPaymentPaid(Payment $payment, PaymentTypeEnum $type): void
    {
        $eventClass = $this->getEventTypeClass($type, PaymentStageEnum::PAID);
        $this->dispatch($eventClass, $payment);
    }

    protected function dispatchPaymentFailed(Payment $payment, PaymentTypeEnum $type): void
    {
        $eventClass = $this->getEventTypeClass($type, PaymentStageEnum::FAILED);
        $this->dispatch($eventClass, $payment);
    }

    protected function dispatchPaymentCanceled(Payment $payment, PaymentTypeEnum $type): void
    {
        $eventClass = $this->getEventTypeClass($type, PaymentStageEnum::CANCELED);
        $this->dispatch($eventClass, $payment);
    }

    private function getPaymentType(Payment $payment): ?PaymentTypeEnum
    {
        $typeColumn = $this->configurator->getTypeColumn();
        $key = $this->repository->getAttribute($payment, $typeColumn);

        return $this->configurator->resolvePaymentType($key);
    }

    private function dispatch(?string $eventClass, ...$args): void
    {
        if (!empty($eventClass) && class_exists($eventClass)) {
            event(new $eventClass(...$args));
        }
    }

    private function getEventTypeClass(PaymentTypeEnum|string $type, PaymentStageEnum|string $key): ?string
    {
        $type = is_string($type) ? $type : $type->name;
        $key = is_string($key) ? $key : $key->name;

        return $this->configurator->getEventClass("$type.$key");
    }
}