<?php

namespace Meridaura\PaymentManager\Drivers;

use Carbon\Carbon;
use Meridaura\PaymentManager\DTO\Error;
use Meridaura\PaymentManager\DTO\WebhookParseData;
use Meridaura\PaymentManager\DTO\WebhookResponse;
use Meridaura\PaymentManager\Enums\PaymentStageEnum;
use Meridaura\PaymentManager\Enums\PaymentTypeEnum;
use Meridaura\PaymentManager\Models\Payment;
use Meridaura\PaymentManager\Traits\UseCoreTrait;

abstract class AbstractWebhook
{
    use UseCoreTrait;

    public function handle(array $allRequestData = []): WebhookResponse
    {
        $data = $this->parseRequestData($allRequestData);

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

        if (!$paymentType) {
            return $this->buildErrorResponse($data, 'invalid_payment_type', ['type' => $this->getRawPaymentType($payment)]);
        }

        $this->syncPaymentState($payment, $data, $paymentType);

        return new WebhookResponse(payment: $payment, parseData: $data);
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

        return Carbon::parse($lastModified)->greaterThanOrEqualTo($data->modifiedDate);
    }

    protected function resolvePaymentType(Payment $payment): ?PaymentTypeEnum
    {
        $typeStr = $this->getRawPaymentType($payment);

        return $this->configurator()->resolvePaymentType($typeStr);
    }

    protected function getRawPaymentType(Payment $payment): string
    {
        return (string) $this->repository()->getAttribute($payment, $this->configurator()->getTypeColumn());
    }

    protected function syncPaymentState(Payment $payment, WebhookParseData $data, PaymentTypeEnum $type): void
    {
        $oldDbStatus = $this->repository()->getAttribute($payment, $this->configurator()->getStageColumn());
        $newDbStatus = $this->configurator()->getStatusByStage($type, $data->stage);

        $this->updateDatabaseStatus($payment, $data, $oldDbStatus, $newDbStatus);

        $oldStageEnum = $this->configurator()->resolveStageFromStatus($type, $oldDbStatus);

        $this->dispatchLifecycleStageIfChanged($payment, $oldStageEnum, $data->stage);
    }

    protected function updateDatabaseStatus(Payment $payment, WebhookParseData $data, ?string $oldDbStatus, ?string $newDbStatus): void
    {
        if ($this->hasDatabaseStatusChanged($oldDbStatus, $newDbStatus)) {
            $this->saveWebhookDataWithNewStatus($payment, $data, $newDbStatus);
            $this->events()->dispatchChangeStatus($payment, $newDbStatus, $oldDbStatus);
        } else {
            $this->saveWebhookDataOnly($payment, $data);
        }
    }

    protected function hasDatabaseStatusChanged(?string $oldDbStatus, ?string $newDbStatus): bool
    {
        return $newDbStatus !== null && $newDbStatus !== $oldDbStatus;
    }

    protected function dispatchLifecycleStageIfChanged(Payment $payment, ?PaymentStageEnum $oldStage, ?PaymentStageEnum $newStage): void
    {
        if ($oldStage !== $newStage) {
            $this->events()->dispatchLifecycleStage($payment, $newStage);
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
            $this->configurator()->getWebhookModifyAtColumName() => $data->modifiedDate,
            $this->configurator()->getWebhookDataColumName() => $data->fullRequestData,
        ]);
    }

    protected function buildErrorResponse(WebhookParseData $data, string $messageKey, array $replace = []): WebhookResponse
    {
        return new WebhookResponse(
            payment: null,
            parseData: $data,
            error: new Error(__("payment-manager::messages.{$messageKey}", $replace))
        );
    }

    abstract function parseRequestData(array $allRequestData = []): WebhookParseData;
}