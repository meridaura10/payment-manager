<?php

namespace Meridaura\PaymentManager\Drivers;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Meridaura\PaymentManager\DTO\PaymentError;
use Meridaura\PaymentManager\DTO\GatewayRequest;
use Meridaura\PaymentManager\DTO\Recurring\Execute\RecurringExecuteParseResponse;
use Meridaura\PaymentManager\DTO\Recurring\Execute\RecurringExecuteRequest;
use Meridaura\PaymentManager\DTO\Recurring\Execute\RecurringExecuteResponse;
use Meridaura\PaymentManager\DTO\Recurring\Setup\RecurringSetupParseResponse;
use Meridaura\PaymentManager\DTO\Recurring\Setup\RecurringSetupRequest;
use Meridaura\PaymentManager\DTO\Recurring\Setup\RecurringSetupResponse;
use Meridaura\PaymentManager\Enums\PaymentApiResponseStatusEnum;
use Meridaura\PaymentManager\Enums\PaymentOperationEnum;
use Meridaura\PaymentManager\Enums\PaymentResponseStatusEnum;
use Meridaura\PaymentManager\Enums\PaymentStageEnum;
use Meridaura\PaymentManager\Enums\PaymentTypeEnum;
use Meridaura\PaymentManager\Models\Payment;
use Meridaura\PaymentManager\Traits\UseCoreTrait;

abstract class AbstractRecurring
{
    use UseCoreTrait;

    private ?string $gatewayName = null;

    protected \UnitEnum|string $paymentType = PaymentTypeEnum::RECURRING;
    protected \UnitEnum|string $setupOperation = PaymentOperationEnum::RECURRING_SETUP;
    protected \UnitEnum|string $executeOperation = PaymentOperationEnum::RECURRING_EXECUTE;

    public function setup(RecurringSetupRequest $setupRequest): RecurringSetupResponse
    {
        $operation = $this->setupOperation;

        $paymentData = array_merge(
            $setupRequest->paymentData,
            $this->getCreatePaymentData($operation),
        );

        $payment = $this->repository()->resolvePayment($this->paymentType, $setupRequest, $paymentData, $this->setupOperation);

        if ($pageUrl = $this->getValidReusableSetupUrl($payment, $operation)) {
            $parsedSetupResponse = new RecurringSetupParseResponse(
                status: PaymentApiResponseStatusEnum::SUCCESS,
                invoice_id: $this->repository()->getAttribute($payment, $this->configurator()->getExternIdColumn()),
                page_url: $pageUrl,
                data: $this->repository()->getAttribute($payment, $this->configurator()->getResponseColumn(), []),
                isReused: true,
            );

            return new RecurringSetupResponse(
                status: PaymentResponseStatusEnum::SUCCESS,
                payment: $payment,
                request: $setupRequest,
                response: $parsedSetupResponse,
                errors: null,
                isReused: true,
            );
        }

        try {
            $gatewayRequest = $this->buildSetupRequest($setupRequest, $payment);
            $httpResponse = $this->sendHttpRequest($gatewayRequest);
            $parsedSetupResponse = $this->parseSetupResponse($httpResponse, $setupRequest, $payment);
        } catch (\Throwable $throwable) {
            $this->markPaymentAsFailed($payment, $operation);

            return new RecurringSetupResponse(
                status: PaymentResponseStatusEnum::ERROR,
                payment: $payment,
                request: $setupRequest,
                response: null,
                errors: new PaymentError(
                    message: __('payment-manager::messages.error_service_unavailable'),
                    systemMessage: 'Internal error during request building or gateway connection.',
                    gatewayMessage: $throwable->getMessage(),
                    code: $throwable->getCode(),
                )
            );
        }

        if (!$this->isValidSetupResponse($parsedSetupResponse)) {
            $this->markPaymentAsFailed($payment, $operation);

            return new RecurringSetupResponse(
                status: PaymentResponseStatusEnum::ERROR,
                payment: $payment,
                request: $setupRequest,
                response: $parsedSetupResponse,
                errors: new PaymentError(
                    message: __('payment-manager::messages.error_invalid_gateway_response'),
                    systemMessage: 'Gateway did not return required data (e.g., page_url or invoice_id).',
                    gatewayMessage: null,
                    code: 502,
                )
            );
        }

        $this->handleSuccessfulSetupResponse($payment, $parsedSetupResponse, $operation);

        return new RecurringSetupResponse(
            status: PaymentResponseStatusEnum::SUCCESS,
            payment: $payment,
            request: $setupRequest,
            response: $parsedSetupResponse,
            errors: null,
        );
    }

    public function execute(RecurringExecuteRequest $executeRequest): RecurringExecuteResponse
    {
        $operation = $this->executeOperation;

        $paymentData = array_merge(
            $executeRequest->paymentData,
            $this->getCreatePaymentData($operation)
        );

        $payment = $this->repository()->resolvePayment($this->paymentType, $executeRequest, $paymentData, $this->executeOperation);

        if ($pageUrl = $this->getValidReusableSetupUrl($payment, $operation)) {
            $parsedSetupResponse = new RecurringExecuteParseResponse(
                status: PaymentApiResponseStatusEnum::SUCCESS,
                invoice_id: $this->repository()->getAttribute($payment, $this->configurator()->getExternIdColumn()),
                page_url: $pageUrl,
                data: $this->repository()->getAttribute($payment, $this->configurator()->getResponseColumn(), []),
            );

            return new RecurringExecuteResponse(
                status: PaymentResponseStatusEnum::SUCCESS,
                payment: $payment,
                request: $executeRequest,
                response: $parsedSetupResponse,
                errors: null,
            );
        }

        try {
            $gatewayRequest = $this->buildExecuteRequest($executeRequest, $payment);
            $httpResponse = $this->sendHttpRequest($gatewayRequest);
            $parsedExecuteResponse = $this->parseExecuteResponse($httpResponse, $executeRequest, $payment);
        } catch (\Throwable $throwable) {
            $this->markPaymentAsFailed($payment, $operation);

            return new RecurringExecuteResponse(
                status: PaymentResponseStatusEnum::ERROR,
                payment: $payment,
                request: $executeRequest,
                response: null,
                errors: new PaymentError(
                    message: __('payment-manager::messages.error_service_unavailable'),
                    systemMessage: 'Internal error during execute request building or gateway connection.',
                    gatewayMessage: $throwable->getMessage(),
                    code: $throwable->getCode(),
                )
            );
        }

        if ($parsedExecuteResponse->status === PaymentApiResponseStatusEnum::SUCCESS) {
            $this->handleSuccessfulExecuteResponse($payment, $parsedExecuteResponse, $operation);
            $status = PaymentResponseStatusEnum::SUCCESS;
        } else {
            $this->markPaymentAsFailed($payment, $operation);
            $status = PaymentResponseStatusEnum::ERROR;
        }

        return new RecurringExecuteResponse(
            status: $status,
            payment: $payment,
            request: $executeRequest,
            response: $parsedExecuteResponse,
            errors: null
        );
    }

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

    public function setGatewayName(string $gateway): static
    {
        $this->gatewayName = $gateway;

        return $this;
    }

    public function getGatewayName(): ?string
    {
        return $this->gatewayName;
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

    protected function handleSuccessfulSetupResponse(Payment $payment, RecurringSetupParseResponse $parsedSetupResponse, \UnitEnum|string $operation): void
    {
        $lifetimeSeconds = $this->configurator()->getLinkLifetime($this->getGatewayName(), $this->paymentType, $operation);

        $updateData = [
            $this->configurator()->getResponseColumn() => $parsedSetupResponse->data,
            $this->configurator()->getPageUrlColumn() => $parsedSetupResponse->page_url,
            $this->configurator()->getExpiresAtColumn() => $lifetimeSeconds ? now()->addSeconds($lifetimeSeconds) : null,
            $this->configurator()->getExternIdColumn() => $parsedSetupResponse->invoice_id,
            $this->configurator()->getStageColumn() => $this->configurator()->getStatusByStage(PaymentStageEnum::PENDING, $this->paymentType, $operation),
        ];

        $this->repository()->update($payment, $updateData);

        $this->events()->dispatchLifecycleStage($payment, PaymentStageEnum::PENDING, $operation);
    }

    protected function handleSuccessfulExecuteResponse(Payment $payment, RecurringExecuteParseResponse $parsedExecuteResponse, \UnitEnum|string $operation): void
    {
        $lifetimeSeconds = $this->configurator()->getLinkLifetime($this->getGatewayName(), $this->paymentType, $operation);

        $this->repository()->update($payment, [
            $this->configurator()->getResponseColumn() => $parsedExecuteResponse->data,
            $this->configurator()->getPageUrlColumn() => $parsedExecuteResponse->page_url,
            $this->configurator()->getExpiresAtColumn() => $lifetimeSeconds ? now()->addSeconds($lifetimeSeconds) : null,
            $this->configurator()->getStageColumn() => $this->configurator()->getStatusByStage(PaymentStageEnum::PENDING, $this->paymentType, $operation),
            $this->configurator()->getExternIdColumn() => $parsedExecuteResponse->invoice_id ?? $this->repository()->getAttribute($payment, $this->configurator()->getExternIdColumn()),
        ]);

        $this->events()->dispatchLifecycleStage($payment, PaymentStageEnum::PENDING, $operation);
    }

    protected function isValidSetupResponse(RecurringSetupParseResponse $parsedSetupResponse): bool
    {
        if ($parsedSetupResponse->status === PaymentApiResponseStatusEnum::ERROR || empty($parsedSetupResponse->page_url)) {
            return false;
        }

        return true;
    }

    abstract protected function buildSetupRequest(RecurringSetupRequest $request, Payment $payment): GatewayRequest;

    abstract protected function parseSetupResponse(Response $response, RecurringSetupRequest $request, Payment $payment): RecurringSetupParseResponse;

    abstract protected function buildExecuteRequest(RecurringExecuteRequest $request, Payment $payment): GatewayRequest;

    abstract protected function parseExecuteResponse(Response $response, RecurringExecuteRequest $request, Payment $payment): RecurringExecuteParseResponse;
}