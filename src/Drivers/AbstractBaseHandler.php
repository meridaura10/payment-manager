<?php

namespace Meridaura\PaymentManager\Drivers;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Meridaura\PaymentManager\DTO\Base\BaseParseResponse;
use Meridaura\PaymentManager\DTO\GatewayRequest;
use Meridaura\PaymentManager\DTO\Recurring\Execute\RecurringExecuteParseResponse;
use Meridaura\PaymentManager\Enums\PaymentApiResponseStatusEnum;
use Meridaura\PaymentManager\Enums\PaymentStageEnum;
use Meridaura\PaymentManager\Models\Payment;
use Meridaura\PaymentManager\Traits\UseCoreTrait;

abstract class AbstractBaseHandler
{
    use UseCoreTrait;

    private ?string $gatewayName = null;

    protected \UnitEnum|string $paymentType = 'custom';

    protected \UnitEnum|string $operation = 'custom';

//    public function setup(BaseOperationRequest $request): BaseOperationResponse
//    {
//        $operation = 'setup';
//
//        $paymentData = array_merge(
//            $request->paymentData,
//            $this->getCreatePaymentData($operation),
//        );
//
//        $payment = $this->repository()->resolvePayment($this->paymentType, $request, $paymentData);
//
//        if ($pageUrl = $this->getValidReusableSetupUrl($payment, $operation)) {
//            $response = new BaseParseResponse(
//                status: PaymentApiResponseStatusEnum::SUCCESS,
//                invoice_id: $this->repository()->getAttribute($payment, $this->configurator()->getExternIdColumn()),
//                page_url: $pageUrl,
//                data: $this->repository()->getAttribute($payment, $this->configurator()->getResponseColumn(), []),
//                isReused: true,
//            );
//
//            return new BaseOperationResponse(
//                status: PaymentResponseStatusEnum::SUCCESS,
//                payment: $payment,
//                request: $request,
//                response: $response,
//                errors: null,
//                isReused: true,
//            );
//        }
//
//        try {
//            $gatewayRequest = $this->buildSetupRequest($request, $payment);
//            $httpResponse = $this->sendHttpRequest($gatewayRequest);
//            $response = $this->parseSetupResponse($httpResponse, $payment);
//        } catch (\Throwable $throwable) {
//            $this->markPaymentAsFailed($payment, $operation);
//
//            return new BaseOperationResponse(
//                status: PaymentResponseStatusEnum::ERROR,
//                payment: $payment,
//                request: $request,
//                response: null,
//                errors: new Error(
//                    message: __('payment-manager::messages.error_service_unavailable'),
//                    systemMessage: 'Internal error during request building or gateway connection.',
//                    gatewayMessage: $throwable->getMessage(),
//                    code: $throwable->getCode(),
//                )
//            );
//        }
//
//        if (!$this->isValidSetupResponse($response)) {
//            $this->markPaymentAsFailed($payment, $operation);
//
//            return new BaseOperationResponse(
//                status: PaymentResponseStatusEnum::ERROR,
//                payment: $payment,
//                request: $request,
//                response: $response,
//                errors: new Error(
//                    message: __('payment-manager::messages.error_invalid_gateway_response'),
//                    systemMessage: 'Gateway did not return required data (e.g., page_url or invoice_id).',
//                    gatewayMessage: null,
//                    code: 502,
//                )
//            );
//        }
//
//        $this->handleSuccessfulSetupResponse($payment, $response, $operation);
//
//        return new BaseOperationResponse(
//            status: PaymentResponseStatusEnum::SUCCESS,
//            payment: $payment,
//            request: $request,
//            response: $response,
//            errors: null,
//        );
//    }

//    protected function buildSetupRequest(BaseOperationRequest $request, Payment $payment): GatewayRequest
//    {
//        $data = [
//            'amount' => $request->amount * 100,
//            'ccy' => 980,
//            'webhookUrl' => $request->webHookUrls ?? null,
//            'redirectUrl' => $request->redirectUrls ?? null,
//            'saveCardData' => [
//                'saveCard' => true,
//                'walletId' => Str::uuid()->toString(),
//            ]
//        ];
//
//        if (isset($request->interval)) {
//            $data['interval'] = $request->interval;
//        }
//
//        $headers['X-Token'] = $this->config['X-Token'] ?? null;
//
//        return new GatewayRequest(
//            url: "{$this->config['base_url']}/api/payment",
//            payload: $data,
//            headers: $headers,
//            method: 'POST',
//        );
//    }
//
//    protected function parseSetupResponse(Response $response, Payment $payment): BaseParseResponse
//    {
//        $json = $response->json();
//
//        $status = isset($json['errCode'])
//            ? PaymentApiResponseStatusEnum::ERROR
//            : PaymentApiResponseStatusEnum::SUCCESS;
//
//        return new BaseParseResponse(
//            status: $status,
//            invoice_id: $json['invoiceId'] ?? null,
//            page_url: $json['pageUrl'] ?? null,
//            data: $json,
//        );
//    }

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

    protected function handleSuccessfulSetupResponse(Payment $payment, BaseParseResponse $response, \UnitEnum|string $operation): void
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

    protected function handleSuccessfulExecuteResponse(Payment $payment, RecurringExecuteParseResponse $parsedExecuteResponse, \UnitEnum|string $operation): void
    {
        $lifetimeSeconds = $this->configurator()->getLinkLifetime($this->getGatewayName(), $this->paymentType, $operation);

        $this->repository()->update($payment, [
            $this->configurator()->getResponseColumn() => $parsedExecuteResponse->data,
            $this->configurator()->getPageUrlColumn() => $parsedExecuteResponse->page_url,
            $this->configurator()->getExpiresAtColumn() => $lifetimeSeconds ? now()->addSeconds($lifetimeSeconds) : null,
            $this->configurator()->getStageColumn() => $this->configurator()->getStatusByStage(PaymentStageEnum::PAID, $this->paymentType, $operation),
            $this->configurator()->getExternIdColumn() => $parsedExecuteResponse->invoice_id ?? $this->repository()->getAttribute($payment, $this->configurator()->getExternIdColumn()),
        ]);

        $this->events()->dispatchLifecycleStage($payment, PaymentStageEnum::PAID, $operation);
    }

    protected function isValidSetupResponse(BaseParseResponse $response): bool
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
