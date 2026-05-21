<?php

namespace Meridaura\PaymentManager\Drivers;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Meridaura\PaymentManager\DTO\Charge\ChargePurchaseParseResponse;
use Meridaura\PaymentManager\DTO\Charge\ChargePurchaseRequest;
use Meridaura\PaymentManager\DTO\Charge\ChargePurchaseResponse;
use Meridaura\PaymentManager\DTO\PaymentError;
use Meridaura\PaymentManager\DTO\GatewayRequest;
use Meridaura\PaymentManager\Enums\PaymentApiResponseStatusEnum;
use Meridaura\PaymentManager\Enums\PaymentOperationEnum;
use Meridaura\PaymentManager\Enums\PaymentResponseStatusEnum;
use Meridaura\PaymentManager\Enums\PaymentStageEnum;
use Meridaura\PaymentManager\Enums\PaymentTypeEnum;
use Meridaura\PaymentManager\Models\Payment;
use Meridaura\PaymentManager\Traits\UseCoreTrait;

abstract class AbstractCharge
{
    use UseCoreTrait;

    private ?string $gatewayName = null;

    protected \UnitEnum|string $paymentType = PaymentTypeEnum::CHARGE;
    protected \UnitEnum|string $purchaseOperation = PaymentOperationEnum::CHARGE_PURCHASE;

    public function purchase(ChargePurchaseRequest $purchaseRequest): ChargePurchaseResponse
    {
        $paymentData = array_merge(
            $purchaseRequest->paymentData,
            $this->getCreatePaymentData(),
        );

        $payment = $this->repository()->resolvePayment($this->paymentType, $purchaseRequest, $paymentData, $this->purchaseOperation);

        if ($pageUrl = $this->getValidReusablePurchaseUrl($payment)) {
            $parsedPurchaseResponse = new ChargePurchaseParseResponse(
                status: PaymentApiResponseStatusEnum::SUCCESS,
                invoice_id: $this->repository()->getAttribute($payment, $this->configurator()->getExternIdColumn()),
                page_url: $pageUrl,
                data: $this->repository()->getAttribute($payment, $this->configurator()->getResponseColumn(), []),
                isReused: true,
            );

            return new ChargePurchaseResponse(
                status: PaymentResponseStatusEnum::SUCCESS,
                payment: $payment,
                request: $purchaseRequest,
                response: $parsedPurchaseResponse,
                errors: null,
                isReused: true,
            );
        }

        try {
            $gatewayRequest = $this->buildPurchaseRequest($purchaseRequest, $payment);
            $httpResponse = $this->sendHttpRequest($gatewayRequest);
            $parsedPurchaseResponse = $this->parsePurchaseResponse($httpResponse, $purchaseRequest, $payment);
        } catch (\Throwable $throwable) {
            $this->markPaymentAsFailed($payment);

            return new ChargePurchaseResponse(
                status: PaymentResponseStatusEnum::ERROR,
                payment: $payment,
                request: $purchaseRequest,
                response: null,
                errors: new PaymentError(
                    message: __('payment-manager::messages.error_service_unavailable'),
                    systemMessage: 'Internal error during request building or gateway connection.',
                    gatewayMessage: $throwable->getMessage(),
                    code: $throwable->getCode(),
                )
            );
        }

        if (!$this->isValidPurchaseResponse($parsedPurchaseResponse)) {
            $this->markPaymentAsFailed($payment);

            return new ChargePurchaseResponse(
                status: PaymentResponseStatusEnum::ERROR,
                payment: $payment,
                request: $purchaseRequest,
                response: $parsedPurchaseResponse,
                errors: new PaymentError(
                    message: __('payment-manager::messages.error_invalid_gateway_response'),
                    systemMessage: 'Gateway did not return required data (e.g., page_url or invoice_id).',
                    gatewayMessage: null,
                    code: 502,
                )
            );
        }

        $this->handleSuccessfulPurchaseResponse($payment, $parsedPurchaseResponse);

        return new ChargePurchaseResponse(
            status: PaymentResponseStatusEnum::SUCCESS,
            payment: $payment,
            request: $purchaseRequest,
            response: $parsedPurchaseResponse,
            errors: null,
        );
    }

    protected function getValidReusablePurchaseUrl(Payment $payment): ?string
    {
        if (!$this->configurator()->getLinkLifetime($this->getGatewayName(), $this->paymentType, $this->purchaseOperation)) {
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

    protected function getCreatePaymentData(): array
    {
        $typeStr = $this->paymentType instanceof \UnitEnum ? $this->paymentType->name : $this->paymentType;
        $operationStr = $this->purchaseOperation instanceof \UnitEnum ? $this->purchaseOperation->name : $this->purchaseOperation;

        return [
            $this->configurator()->getTypeColumn() => $this->configurator()->getTypeValue($typeStr),
            $this->configurator()->getPaymentGatewayColumn() => $this->gatewayName,
            $this->configurator()->getStageColumn() => $this->configurator()->getStatusByStage(PaymentStageEnum::CREATED, $this->paymentType, $this->purchaseOperation),
            $this->configurator()->getOperationColumn() => $this->configurator()->getOperationValue($operationStr) ?? $operationStr,
        ];
    }

    protected function markPaymentAsFailed(Payment $payment): void
    {
        $failedStage = $this->configurator()->getStatusByStage(PaymentStageEnum::FAILED, $this->paymentType, $this->purchaseOperation);

        if ($failedStage) {
            $this->repository()->update($payment, [
                $this->configurator()->getStageColumn() => $failedStage
            ]);
        }

        $this->events()->dispatchLifecycleStage($payment, PaymentStageEnum::FAILED, $this->purchaseOperation);
    }

    protected function sendHttpRequest(GatewayRequest $gatewayRequest): Response
    {
        return Http::withHeaders($gatewayRequest->headers)
            ->send($gatewayRequest->method, $gatewayRequest->url, [$gatewayRequest->encoding => $gatewayRequest->payload]);
    }

    protected function handleSuccessfulPurchaseResponse(Payment $payment, ChargePurchaseParseResponse $parsedPurchaseResponse): void
    {
        $lifetimeSeconds = $this->configurator()->getLinkLifetime($this->getGatewayName(), $this->paymentType, $this->purchaseOperation);

        $updateData = [
            $this->configurator()->getResponseColumn() => $parsedPurchaseResponse->data,
            $this->configurator()->getPageUrlColumn() => $parsedPurchaseResponse->page_url,
            $this->configurator()->getExternIdColumn() => $parsedPurchaseResponse->invoice_id,
            $this->configurator()->getExpiresAtColumn() => $lifetimeSeconds ? now()->addSeconds($lifetimeSeconds) : null,
            $this->configurator()->getStageColumn() => $this->configurator()->getStatusByStage(PaymentStageEnum::PENDING, $this->paymentType, $this->purchaseOperation),
        ];

        $this->repository()->update($payment, $updateData);

        $this->events()->dispatchLifecycleStage($payment, PaymentStageEnum::PENDING, $this->purchaseOperation);
    }

    protected function isValidPurchaseResponse(ChargePurchaseParseResponse $parsedPurchaseResponse): bool
    {
        if ($parsedPurchaseResponse->status === PaymentApiResponseStatusEnum::ERROR) {
            return false;
        }

        if (empty($parsedPurchaseResponse->page_url) && empty($parsedPurchaseResponse->html_form)) {
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

    abstract protected function buildPurchaseRequest(ChargePurchaseRequest $request, Payment $payment): GatewayRequest;

    abstract protected function parsePurchaseResponse(Response $response, ChargePurchaseRequest $purchaseRequest, Payment $payment): ChargePurchaseParseResponse;
}