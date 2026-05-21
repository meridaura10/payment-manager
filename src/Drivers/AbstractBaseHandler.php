<?php

namespace Meridaura\PaymentManager\Drivers;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Meridaura\PaymentManager\DTO\GatewayRequest;
use Meridaura\PaymentManager\Enums\PaymentApiResponseStatusEnum;
use Meridaura\PaymentManager\Enums\PaymentStageEnum;
use Meridaura\PaymentManager\Models\Payment;
use Meridaura\PaymentManager\Traits\UseCoreTrait;

abstract class AbstractBaseHandler
{
    use UseCoreTrait;

    private ?string $gatewayName = null;

    protected \UnitEnum|string $paymentType = 'custom';

    protected function getValidReusableSetupUrl(Payment $payment, \UnitEnum|string $operation): ?string
    {
        if (!$this->configurator()->getLinkLifetime($this->getGatewayName(), $this->paymentType, $operation)) {
            return null;
        }

        $pageUrlColumn = $this->configurator()->getPageUrlColumn();
        $expiresAtColumn = $this->configurator()->getExpiresAtColumn();
        $pageUrl = $this->repository()->getAttribute($payment, $pageUrlColumn);

        if (empty($pageUrl)) {
            return null;
        }

        $expiresAt = $this->repository()->getAttribute($payment, $expiresAtColumn);

        if (is_null($expiresAt) || \Carbon\Carbon::parse($expiresAt)->isPast()) {
            return null;
        }

        return $pageUrl;
    }

    protected function getCreatePaymentData(\UnitEnum|string $operation): array
    {
        $typeStr = $this->paymentType instanceof \UnitEnum ? $this->paymentType->name : $this->paymentType;
        $operationStr = $operation instanceof \UnitEnum ? $operation->name : $operation;
        
        return [
            $this->configurator()->getTypeColumn() => $this->configurator()->getTypeValue($typeStr),
            $this->configurator()->getPaymentGatewayColumn() => $this->gatewayName,
            $this->configurator()->getStageColumn() => $this->configurator()->getStatusByStage(PaymentStageEnum::CREATED, $this->paymentType, $operation),
            $this->configurator()->getOperationColumn() => $this->configurator()->getOperationValue($operationStr) ?? $operationStr,
        ];
    }

    protected function markPaymentAsFailed(Payment $payment, \UnitEnum|string $operation): void
    {
        $failedStage = $this->configurator()->getStatusByStage(PaymentStageEnum::FAILED, $this->paymentType, $operation);

        if ($failedStage) {
            $this->repository()->update($payment, [
                $this->configurator()->getStageColumn() => $failedStage
            ]);
        }

        $this->events()->dispatchLifecycleStage($payment, PaymentStageEnum::FAILED, $operation);
    }

    protected function sendHttpRequest(GatewayRequest $gatewayRequest): Response
    {
        return Http::withHeaders($gatewayRequest->headers)
            ->send($gatewayRequest->method, $gatewayRequest->url, [$gatewayRequest->encoding => $gatewayRequest->payload]);
    }

    protected function handleSuccessfulSetupResponse(Payment $payment, mixed $response, \UnitEnum|string $operation): void
    {
        $lifetimeSeconds = $this->configurator()->getLinkLifetime($this->getGatewayName(), $this->paymentType, $operation);

        $updateData = [
            $this->configurator()->getResponseColumn() => $response->data,
            $this->configurator()->getPageUrlColumn() => $response->page_url,
            $this->configurator()->getExpiresAtColumn() => $lifetimeSeconds ? now()->addSeconds($lifetimeSeconds) : null,
            $this->configurator()->getExternIdColumn() => $response->invoice_id,
            $this->configurator()->getStageColumn() => $this->configurator()->getStatusByStage(PaymentStageEnum::PENDING, $this->paymentType, $operation),
        ];

        $this->repository()->update($payment, $updateData);

        $this->events()->dispatchLifecycleStage($payment, PaymentStageEnum::PENDING, $operation);
    }

    protected function handleSuccessfulExecuteResponse(Payment $payment, mixed $parseResponse, \UnitEnum|string $operation): void
    {
        $lifetimeSeconds = $this->configurator()->getLinkLifetime($this->getGatewayName(), $this->paymentType, $operation);

        $this->repository()->update($payment, [
            $this->configurator()->getResponseColumn() => $parseResponse->data,
            $this->configurator()->getPageUrlColumn() => $parseResponse->page_url,
            $this->configurator()->getExpiresAtColumn() => $lifetimeSeconds ? now()->addSeconds($lifetimeSeconds) : null,
            $this->configurator()->getStageColumn() => $this->configurator()->getStatusByStage(PaymentStageEnum::PAID, $this->paymentType, $operation),
            $this->configurator()->getExternIdColumn() => $parseResponse->invoice_id ?? $this->repository()->getAttribute($payment, $this->configurator()->getExternIdColumn()),
        ]);

        $this->events()->dispatchLifecycleStage($payment, PaymentStageEnum::PAID, $operation);
    }

    protected function isValidSetupResponse(mixed $response): bool
    {
        if ($response->status === PaymentApiResponseStatusEnum::ERROR || empty($response->page_url)) {
            return false;
        }

        return true;
    }


    public function setGatewayName(string $gateway): static
    {
        $this->gatewayName = $gateway;

        return $this;
    }

    public function getGatewayName(): ?string
    {
        return $this->gatewayName;
    }
}
