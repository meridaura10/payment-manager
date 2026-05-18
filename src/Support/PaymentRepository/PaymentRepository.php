<?php

namespace Meridaura\PaymentManager\Support\PaymentRepository;

use Meridaura\PaymentManager\Enums\PaymentStageEnum;
use Meridaura\PaymentManager\Models\Payment;
use Meridaura\PaymentManager\Support\Configurator\ConfiguratorInterface;
use Meridaura\PaymentManager\Support\EventManager\EventManagerInterface;
use RuntimeException;

class PaymentRepository implements PaymentRepositoryInterface
{
    protected array $creators = [];
    protected ?\Closure $defaultCreator = null;

    public function __construct(
        protected ConfiguratorInterface $configurator,
    ) {
        //
    }

    public function setDefaultCreator(\Closure $callback): static
    {
        $this->defaultCreator = $callback;

        return $this;
    }

    public function setCreatorUsing(\UnitEnum|array|string $type, \Closure $callback, \UnitEnum|string|array|null $operation = null): static
    {
        $types = is_array($type) ? $type : [$type];
        $operations = is_null($operation) ? [] : array_filter(is_array($operation) ? $operation : [$operation]);

        foreach ($types as $typeItem) {
            $typeStr = $typeItem instanceof \UnitEnum ? $typeItem->name : $typeItem;

            if (!isset($this->creators[$typeStr])) {
                $this->creators[$typeStr] = [];
            }

            if (empty($operations)) {
                $this->creators[$typeStr]['*'] = $callback;
            } else {
                foreach ($operations as $operationItem) {
                    $operationStr = $operationItem instanceof \UnitEnum ? $operationItem->name : $operationItem;
                    $this->creators[$typeStr][$operationStr] = $callback;
                }
            }
        }

        return $this;
    }

    public function resolvePayment(\UnitEnum|string $type, mixed $dto, array $paymentData = [], \UnitEnum|string|null $operation = null): Payment
    {
        $typeStr = $type instanceof \UnitEnum ? $type->name : $type;
        $operationStr = $operation instanceof \UnitEnum ? $operation->name : $operation;

        if (!is_null($operation)) {
            $creator = $this->creators[$typeStr][$operationStr] ?? ($this->creators[$typeStr]['*'] ?? null);
        } else {
            $creator = $this->creators[$typeStr]['*'] ?? null;
        }

        if (!is_callable($creator) && is_callable($this->defaultCreator)) {
            $creator = $this->defaultCreator;
        }

        if (!is_callable($creator)) {
            $opMessage = $operationStr ? "::$operationStr" : "";
            throw new RuntimeException("Логіка створення платежу для типу [{$typeStr}{$opMessage}] не визначена.");
        }

        $payment = $creator($dto, $paymentData);

        if ($payment->wasRecentlyCreated) {
            $this->events()->dispatchLifecycleStage($payment, PaymentStageEnum::CREATED, $operation);
        }

        return $payment;
    }

    public function getModel(): Payment
    {
         return new ($this->configurator->getPaymentModel());
    }

    public function findByExternId(string $value): ?Payment
    {
        $model = $this->getModel();
        $column = $this->configurator->getExternIdColumn();

        return $model::query()->where($column, $value)->first();
    }

    public function update(Payment $payment, array $attributes): void
    {
        $validAttributes = array_filter(
            $attributes,
            fn($key) => !empty($key) && is_string($key),
            ARRAY_FILTER_USE_KEY
        );

        if (empty($validAttributes)) {
            return;
        }

        $statusColumn = $this->configurator->getStageColumn();
        $statusChanged = false;
        $oldStatus = null;
        $newStatus = null;

        if (array_key_exists($statusColumn, $validAttributes)) {
            $oldStatus = $payment->getAttribute($statusColumn);
            $newStatus = $validAttributes[$statusColumn];

            if ($oldStatus !== $newStatus) {
                $statusChanged = true;
            }
        }

        $payment->forceFill($validAttributes);

        if ($this->configurator->isSaveQuietlyEnabled()) {
            $payment->saveQuietly();
        } else {
            $payment->save();
        }

        if ($statusChanged) {
            $this->events()->dispatchChangeStatus($payment, $newStatus, $oldStatus);
        }
    }
    public function getAttribute(Payment $payment, string $key, mixed $default = null): mixed
    {
        $dotNotationPath = str_replace('->', '.', $key);

        return data_get($payment, $dotNotationPath, $default);
    }

    public function events(): EventManagerInterface
    {
        return app(EventManagerInterface::class);
    }
}