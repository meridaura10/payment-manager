<?php

namespace Meridaura\PaymentManager\Drivers;

use Meridaura\PaymentManager\DTO\Error;
use Meridaura\PaymentManager\DTO\WebhookParseData;
use Meridaura\PaymentManager\DTO\WebhookResponse;
use Meridaura\PaymentManager\Models\Payment;
use Meridaura\PaymentManager\Traits\UseCoreTrait;

abstract class AbstractWebhook
{
    use UseCoreTrait;

    public function handle(array $allRequestData = []): WebhookResponse
    {
        $data = $this->parseRequestData($allRequestData);
        $payment = null;

        if ($externId = $data->externId) {
            $payment = $this->repository()->findByExternId($externId);
        }

        if (is_null($payment)) {
            return new WebhookResponse(
                payment: null,
                parseData: $data,
                error: new Error(__('payment-manager::messages.payment_not_found', [
                    'id' => $externId ?? 'unknown'
                ], "Payment not found for invoice ID: {$externId}"))
            );
        }

        $newStatus = $data->status;

        if (is_null($newStatus)) {
            return new WebhookResponse(
                payment: $payment,
                parseData: $data,
                error: new Error(__('payment-manager::messages.invalid_status', [
                    'id' => $externId ?? 'unknown'
                ], "Unmapped or invalid status received for invoice ID: {$externId}"))
            );
        }

        $oldStatus = $payment->{$this->configurator()->getStatusColumn()};

        if ($newStatus !== $oldStatus) {
            $this->updatePaymentForChangeStatus($payment, [
                'new' => $newStatus,
                'old' => $oldStatus,
            ], $data);
        }

        return new WebhookResponse(
            payment: $payment,
            parseData: $data,
        );
    }

    protected function updatePaymentForChangeStatus(Payment $payment, array $statuses, WebhookParseData $data): void
    {
        $this->repository()->update($payment, [
            $this->configurator()->getStatusColumn() => $statuses['new'],
            $this->configurator()->getWebhookModifyAtColumName() => $data->modifiedDate ?? now(),
            $this->configurator()->getWebhookDataColumName() => $data->fullRequestData,
        ]);

        $this->fireStatusChangedEvent($payment, $statuses['old'], $statuses['new'], $data);
    }

    protected function fireStatusChangedEvent($payment, $oldStatus, $newStatus, WebhookParseData $data): void
    {
        $eventClass = $this->configurator()->getEventClass($data->status->value);

        if ($eventClass && class_exists($eventClass)) {
            event(new $eventClass($payment, $oldStatus, $newStatus, $data));
        }
    }

    abstract function parseRequestData(array $allRequestData = []): WebhookParseData;
}