<?php

namespace Meridaura\PaymentManager\Drivers;

use Carbon\Carbon;
use Meridaura\PaymentManager\Contracts\HandlesPaymentTypeWebhookInterface;
use Meridaura\PaymentManager\DTO\PaymentError;
use Meridaura\PaymentManager\DTO\Webhook\WebhookParseData;
use Meridaura\PaymentManager\DTO\Webhook\WebhookResponse;
use Meridaura\PaymentManager\Enums\PaymentStageEnum;
use Meridaura\PaymentManager\Models\Payment;
use Meridaura\PaymentManager\Traits\UseCoreTrait;

abstract class AbstractWebhook
{
    use UseCoreTrait;

    private ?AbstractDriver $driver = null;

    protected array $allRequestData = [];

    protected array $allRequestHeaders = [];

    public function handle(array $allRequestData = [], array $headers = []): WebhookResponse
    {
        $this->allRequestData = $allRequestData;
        $this->allRequestHeaders = $headers;

        $data = $this->parseRequestData($allRequestData, $headers);

        $payment = $this->findPayment($data->externId);

        if (is_null($data->stage)) {
            return $this->buildErrorResponse($data, 'unknown_stage', [
                'externId' => $data->externId ?? 'unknown'
            ]);
        }

        if (!$payment) {
            return $this->buildErrorResponse($data, 'payment_not_found', ['externId' => $data->externId ?? 'unknown']);
        }

        if ($this->isWebhookOutdated($payment, $data)) {
            return $this->buildErrorResponse($data, 'webhook_outdated', ['externId' => $data->externId ?? 'unknown']);
        }

        $paymentType = $this->resolvePaymentType($payment);
        $paymentOperation = $this->resolvePaymentOperation($payment);

        if (!$paymentType) {
            return $this->buildErrorResponse($data, 'invalid_payment_type', ['type' => $this->getRawPaymentType($payment)]);
        }

        $typeHandlerClass = $this->driver()?->getTypeClass($paymentType);

        if ($typeHandlerClass instanceof HandlesPaymentTypeWebhookInterface) {
            return $typeHandlerClass->handleWebhook($payment, $data);
        }

        if ($error = $this->beforeHooks($payment, $data, $paymentType, $paymentOperation, $typeHandlerClass)) {
            return new WebhookResponse(payment: $payment, parseData: $data, error: $error);
        }

        $this->defaultHandler($payment, $data, $paymentType, $paymentOperation);

        if ($error = $this->afterHooks($payment, $data, $paymentType, $paymentOperation, $typeHandlerClass)) {
            return new WebhookResponse(payment: $payment, parseData: $data, error: $error);
        }

        return new WebhookResponse(payment: $payment, parseData: $data);
    }

    protected function beforeHooks(Payment $payment, WebhookParseData $data, \UnitEnum|string $type, \UnitEnum|string|null $operation, ?object $typeHandlerClass = null): ?PaymentError
    {
        $typeStr = $type instanceof \UnitEnum ? $type->name : $type;
        $hookMethod = 'webhookBefore' . ucfirst(strtolower($typeStr)) . 'Handler';

        $targets = array_filter([$this, $typeHandlerClass]);

        foreach ($targets as $target) {
            if (method_exists($target, $hookMethod)) {
                $error = $target->{$hookMethod}($payment, $data, $operation);
                if ($error instanceof PaymentError) return $error;
            }

            if (method_exists($target, 'webhookBeforeHandler')) {
                $error = $target->webhookBeforeHandler($payment, $data, $operation);
                if ($error instanceof PaymentError) return $error;
            }
        }

        return null;
    }

    protected function afterHooks(Payment $payment, WebhookParseData $data, \UnitEnum|string $type, \UnitEnum|string|null $operation, ?object $typeHandlerClass = null): ?PaymentError
    {
        $typeStr = $type instanceof \UnitEnum ? $type->name : $type;
        $hookMethod = 'webhookAfter' . ucfirst(strtolower($typeStr)) . 'Handler';

        $targets = array_filter([$this, $typeHandlerClass]);

        foreach ($targets as $target) {
            if (method_exists($target, $hookMethod)) {
                $error = $target->{$hookMethod}($payment, $data, $operation);
                if ($error instanceof PaymentError) return $error;
            }
        }

        return null;
    }

    protected function findPayment(string|int|null $externId): ?Payment
    {
        if (!$externId) {
            return null;
        }

        return $this->repository()->findByExternId($externId);
    }

    protected function isWebhookOutdated(Payment $payment, WebhookParseData $data): bool
    {
        $lastModified = $this->repository()->getAttribute($payment, $this->configurator()->getWebhookModifyAtColumName());

        if (!$lastModified || !$data->modifiedDate) {
            return false;
        }

        return Carbon::parse($lastModified)->greaterThan($data->modifiedDate);
    }

    protected function resolvePaymentType(Payment $payment): \UnitEnum|string|null
    {
        $typeStr = $this->getRawPaymentType($payment);

        return $this->configurator()->resolvePaymentType($typeStr);
    }

    protected function getRawPaymentType(Payment $payment): string
    {
        return (string) $this->repository()->getAttribute($payment, $this->configurator()->getTypeColumn());
    }

    protected function resolvePaymentOperation(Payment $payment): \UnitEnum|string|null
    {
        $operationColumn = $this->configurator()->getOperationColumn();

        if (!$operationColumn) {
            return null;
        }

        $operationStr = (string) $this->repository()->getAttribute($payment, $operationColumn);

        return $this->configurator()->resolveOperation($operationStr) ?? $operationStr;
    }

    protected function defaultHandler(Payment $payment, WebhookParseData $data, \UnitEnum|string $type, \UnitEnum|string|null $operation): void
    {
        $oldDbStatus = $this->repository()->getAttribute($payment, $this->configurator()->getStageColumn());
        $newDbStatus = $this->configurator()->getStatusByStage($data->stage, $type, $operation);

        $this->updateDatabaseStatus($payment, $data, $oldDbStatus, $newDbStatus, $operation);

        $oldStageEnum = $this->configurator()->resolveStageFromStatus($oldDbStatus, $type, $operation);

        $this->dispatchLifecycleStageIfChanged($payment, $oldStageEnum, $data->stage, $operation);
    }

    protected function updateDatabaseStatus(Payment $payment, WebhookParseData $data, ?string $oldDbStatus, ?string $newDbStatus, \UnitEnum|string|null $operation): void
    {
        if ($this->hasDatabaseStatusChanged($oldDbStatus, $newDbStatus)) {
            $this->saveWebhookDataWithNewStatus($payment, $data, $newDbStatus);
            $this->events()->dispatchChangeStatus($payment, $newDbStatus, $oldDbStatus, $operation);
        } else {
            $this->saveWebhookDataOnly($payment, $data);
        }
    }

    protected function hasDatabaseStatusChanged(?string $oldDbStatus, ?string $newDbStatus): bool
    {
        return $newDbStatus !== null && $newDbStatus !== $oldDbStatus;
    }

    protected function dispatchLifecycleStageIfChanged(Payment $payment, ?PaymentStageEnum $oldStage, ?PaymentStageEnum $newStage, \UnitEnum|string|null $operation): void
    {
        if ($oldStage !== $newStage) {
            $this->events()->dispatchLifecycleStage($payment, $newStage, $operation);
        }
    }

    protected function saveWebhookDataWithNewStatus(Payment $payment, WebhookParseData $data, string $newDbStatus): void
    {
        $this->repository()->update($payment, [
            $this->configurator()->getStageColumn() => $newDbStatus,
            $this->configurator()->getWebhookModifyAtColumName() => $data->modifiedDate ?? now(),
            $this->configurator()->getWebhookDataColumName() => $data->fullRequestData,
        ]);
    }

    protected function saveWebhookDataOnly(Payment $payment, WebhookParseData $data): void
    {
        $this->repository()->update($payment, [
            $this->configurator()->getWebhookDataColumName() => $data->fullRequestData,
        ]);
    }

    protected function buildErrorResponse(WebhookParseData $data, string $messageKey, array $replace = []): WebhookResponse
    {
        return new WebhookResponse(
            payment: null,
            parseData: $data,
            error: new PaymentError(__("payment-manager::messages.{$messageKey}", $replace))
        );
    }

    public function setDriver(AbstractDriver $driver): static
    {
        $this->driver = $driver;

        return $this;
    }

    public function driver(): ?AbstractDriver
    {
        return $this->driver;
    }

    abstract function parseRequestData(array $allRequestData = [], array $headers = []): WebhookParseData;
}
