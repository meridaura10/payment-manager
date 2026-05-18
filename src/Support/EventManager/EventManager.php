<?php

namespace Meridaura\PaymentManager\Support\EventManager;

use Meridaura\PaymentManager\Enums\PaymentEventEnum;
use Meridaura\PaymentManager\Models\Payment;
use Meridaura\PaymentManager\Support\Configurator\ConfiguratorInterface;
use Meridaura\PaymentManager\Support\PaymentRepository\PaymentRepositoryInterface;

class EventManager implements EventManagerInterface
{
    public function __construct(
        protected ConfiguratorInterface $configurator,
        protected PaymentRepositoryInterface $repository,
    ) {
    }

    public function dispatchLifecycleStage(Payment $payment, \UnitEnum|string $stage, \UnitEnum|string|null $operation = null): void
    {
        $type = $this->getPaymentType($payment);
        $operation = $operation ?? $this->getPaymentOperation($payment);
        $stageName = $stage instanceof \UnitEnum ? $stage->name : $stage;

        $eventClass = $this->configurator->getEventClass($stageName, $type, $operation);

        $this->dispatch($eventClass, $payment);
    }

    public function dispatchChangeStatus(Payment $payment, string $newStatus, ?string $oldStatus = null, \UnitEnum|string|null $operation = null): void
    {
        $type = $this->getPaymentType($payment);
        $operation = $operation ?? $this->getPaymentOperation($payment);
        $eventName = PaymentEventEnum::STATUS_CHANGED->name;

        $globalEventClass = $this->configurator->getEventClass($eventName);
        $this->dispatch($globalEventClass, $payment, $oldStatus, $newStatus);

        $specificEventClass = $this->configurator->getEventClass($eventName, $type, $operation);

        if ($specificEventClass && $specificEventClass !== $globalEventClass) {
            $this->dispatch($specificEventClass, $payment, $oldStatus, $newStatus);
        }
    }

    private function getPaymentType(Payment $payment): \UnitEnum|string|null
    {
        $typeColumn = $this->configurator->getTypeColumn();
        $key = $this->repository->getAttribute($payment, $typeColumn);

        if (!$key) {
            return null;
        }

        return $this->configurator->resolvePaymentType($key) ?? $key;
    }

    private function getPaymentOperation(Payment $payment): \UnitEnum|string|null
    {
        $operationColumn = $this->configurator->getOperationColumn();

        if (!$operationColumn) {
            return null;
        }

        $key = $this->repository->getAttribute($payment, $operationColumn);

        if (!$key) {
            return null;
        }

        return $this->configurator->resolveOperation($key);
    }

    private function dispatch(?string $eventClass, ...$args): void
    {
        if (!empty($eventClass) && class_exists($eventClass)) {
            event(new $eventClass(...$args));
        }
    }
}