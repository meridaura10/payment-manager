<?php

namespace Meridaura\PaymentManager\Drivers;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Meridaura\PaymentManager\DTO\GatewayRequest;
use Meridaura\PaymentManager\DTO\Error;
use Meridaura\PaymentManager\DTO\PaymentPurchaseRequest;
use Meridaura\PaymentManager\DTO\PaymentPurchaseApiResponse;
use Meridaura\PaymentManager\DTO\PaymentPurchaseResponse;
use Meridaura\PaymentManager\Enums\PaymentApiResponseStatusEnum;
use Meridaura\PaymentManager\Enums\PaymentResponseStatusEnum;
use Meridaura\PaymentManager\Enums\PaymentTypeEnum;
use Meridaura\PaymentManager\Enums\PaymentStageEnum;
use Meridaura\PaymentManager\Models\Payment;
use Meridaura\PaymentManager\Traits\UseCoreTrait;

abstract class AbstractCharge
{
    use UseCoreTrait;
    private ?string $gatewayName = null;

    public function purchase(PaymentPurchaseRequest $purchaseRequest): PaymentPurchaseResponse
    {
        $paymentData = array_merge(
            $purchaseRequest->paymentData,
            $this->getCreatePaymentData(),
        );

        $payment = $this->repository()->createPaymentPurchase($purchaseRequest, $paymentData);

        if ($pageUrl = $this->getValidReusableUrl($payment)) {
            $reuseApi = new PaymentPurchaseApiResponse(
                status: PaymentApiResponseStatusEnum::SUCCESS,
                invoice_id: $this->repository()->getAttribute($payment, $this->configurator()->getExternIdColumn()),
                page_url: $pageUrl,
                data: $this->repository()->getAttribute($payment, $this->configurator()->getResponseColumn(), []),
                isReused: true,
            );

            return new PaymentPurchaseResponse(
                status: PaymentResponseStatusEnum::SUCCESS,
                payment: $payment,
                purchaseRequest: $purchaseRequest,
                gatewayResponse: $reuseApi,
                errors: null,
                isReused: true,
            );
        }

        try {
            $gatewayRequest = $this->buildApiRequest($purchaseRequest, $payment);
            $httpResponse = $this->sendHttpRequest($gatewayRequest);
            $gatewayResponse = $this->buildApiResponse($httpResponse, $payment);

        } catch (\Throwable $throwable) {
            $this->markPaymentAsFailed($payment);

            return new PaymentPurchaseResponse(
                status: PaymentResponseStatusEnum::ERROR,
                payment: $payment,
                purchaseRequest: $purchaseRequest,
                gatewayResponse: null,
                errors: new Error(
                    message: __('payment-manager::messages.error_service_unavailable'),
                    systemMessage: 'Internal error during request building or gateway connection.',
                    gatewayMessage: $throwable->getMessage(),
                    code: $throwable->getCode(),
                )
            );
        }

        if (!$this->isValidGatewayResponse($gatewayResponse)) {
            $this->markPaymentAsFailed($payment);

            return new PaymentPurchaseResponse(
                status: PaymentResponseStatusEnum::ERROR,
                payment: $payment,
                purchaseRequest: $purchaseRequest,
                gatewayResponse: $gatewayResponse,
                errors: new Error(
                    message: __('payment-manager::messages.error_invalid_gateway_response'),
                    systemMessage: 'Gateway did not return required data (e.g., page_url or invoice_id).',
                    gatewayMessage: null,
                    code: 502,
                )
            );
        }

        $this->handleSuccessfulGatewayResponse($payment, $gatewayResponse);

        return new PaymentPurchaseResponse(
            status: PaymentResponseStatusEnum::SUCCESS,
            payment: $payment,
            purchaseRequest: $purchaseRequest,
            gatewayResponse: $gatewayResponse,
            errors: null,
        );
    }

    protected function getValidReusableUrl(Payment $payment): ?string
    {
        if (!$this->configurator()->isReuseLinksEnabled($this->getGatewayName())) {
            return null;
        }

        $pageUrlColumn = $this->configurator()->getPageUrlColumn();
        $expiresAtColumn = $this->configurator()->getExpiresAtColumn();

        $pageUrl = $this->repository()->getAttribute($payment, $pageUrlColumn);

        if (empty($pageUrl)) {
            return null;
        }

        $expiresAt = $this->repository()->getAttribute($payment, $expiresAtColumn);

        if ($expiresAt && \Carbon\Carbon::parse($expiresAt)->isPast()) {
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

    protected function getCreatePaymentData(): array
    {
        $data = [
            $this->configurator()->getTypeColumn() => $this->configurator()->getTypeValue(PaymentTypeEnum::MANUAL->name),
            $this->configurator()->getPaymentGatewayColumn() => $this->gatewayName,
        ];

        $createdStatus = $this->configurator()->getStatusByStage(PaymentTypeEnum::MANUAL, PaymentStageEnum::CREATED);

        if ($createdStatus) {
            $data[$this->configurator()->getStageColumn()] = $createdStatus;
        }

        return $data;
    }

    protected function markPaymentAsFailed(Payment $payment): void
    {
        $failedStage = $this->configurator()->getStatusByStage(PaymentTypeEnum::MANUAL, PaymentStageEnum::FAILED);

        if ($failedStage) {
            $this->repository()->update($payment, [
                $this->configurator()->getStageColumn() => $failedStage
            ]);
        }

        $this->events()->dispatchLifecycleStage($payment, PaymentStageEnum::FAILED);
    }

    protected function sendHttpRequest(GatewayRequest $gatewayRequest): Response
    {
        return Http::withHeaders($gatewayRequest->headers)
            ->send($gatewayRequest->method, $gatewayRequest->url, [$gatewayRequest->encoding => $gatewayRequest->payload]);
    }

    protected function handleSuccessfulGatewayResponse(Payment $payment, PaymentPurchaseApiResponse $gatewayResponse): void
    {
        $lifetimeSeconds = $this->configurator()->getLinkLifetime($this->getGatewayName());

        $updateData = [
            $this->configurator()->getResponseColumn() => $gatewayResponse->data,
            $this->configurator()->getPageUrlColumn() => $gatewayResponse->page_url,
            $this->configurator()->getExternIdColumn() => $gatewayResponse->invoice_id,
            $this->configurator()->getExpiresAtColumn() => $lifetimeSeconds ? now()->addSeconds($lifetimeSeconds) : null,
        ];

        $this->repository()->update($payment, $updateData);

        $this->events()->dispatchLifecycleStage($payment, PaymentStageEnum::PENDING);
    }

    protected function isValidGatewayResponse(PaymentPurchaseApiResponse $gatewayResponse): bool
    {
        if ($gatewayResponse->status === PaymentApiResponseStatusEnum::ERROR || empty($gatewayResponse->page_url)) {
            return false;
        }

        return true;
    }

    abstract public function buildApiRequest(PaymentPurchaseRequest $purchaseRequest, Payment $payment): GatewayRequest;

    abstract public function buildApiResponse(Response $httpResponse, Payment $payment): PaymentPurchaseApiResponse;
}