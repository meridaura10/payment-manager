<?php

namespace Meridaura\PaymentManager\Drivers;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Meridaura\PaymentManager\DTO\GatewayRequest;
use Meridaura\PaymentManager\DTO\Error;
use Meridaura\PaymentManager\DTO\PaymentPurchaseRequest;
use Meridaura\PaymentManager\DTO\PaymentPurchaseApiResponse;
use Meridaura\PaymentManager\DTO\PaymentPurchaseResponse;
use Meridaura\PaymentManager\Enums\PaymentApiResponseStatusEnums;
use Meridaura\PaymentManager\Enums\PaymentResponseStatusEnum;
use Meridaura\PaymentManager\Enums\PaymentTypeEnums;
use Meridaura\PaymentManager\Enums\PaymentStateEnums;
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

        if ($this->getValidReusableUrl($payment)) {
            return new PaymentPurchaseResponse(
                status: PaymentResponseStatusEnum::SUCCESS,
                payment: $payment,
                purchaseRequest: $purchaseRequest,
                gatewayResponse: null,
                errors: null
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
                    message: __('payment-manager::messages.error_service_unavailable', ['default' => 'Payment service is temporarily unavailable.']),
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
                    message: __('payment-manager::messages.error_invalid_gateway_response', ['default' => 'Payment gateway returned an invalid response.']),
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
            errors: null
        );
    }

    private function getValidReusableUrl(Payment $payment): ?string
    {
        if (!$this->configurator()->isReuseLinksEnabled($this->getGatewayName())) {
            return null;
        }

        $pageUrlColumn = $this->configurator()->getPageUrlColumn();
        $expiresAtColumn = $this->configurator()->getExpiresAtColumn();

        $pageUrl = $payment->{$pageUrlColumn} ?? null;

        if (empty($pageUrl)) {
            return null;
        }

        $expiresAt = $payment->{$expiresAtColumn} ?? null;

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

    private function getCreatePaymentData(): array
    {
        $data = [
            $this->configurator()->getTypeColumn() => $this->configurator()->getTypeValue(PaymentTypeEnums::MANUAL->value),
            $this->configurator()->getPaymentGatewayColumn() => $this->gatewayName,
        ];

        $createdStatus = $this->configurator()->getStatusValue(PaymentTypeEnums::MANUAL, PaymentStateEnums::CREATED);

        if ($createdStatus) {
            $data[$this->configurator()->getStatusColumn()] = $createdStatus;
        }

        return $data;
    }

    protected function markPaymentAsFailed(Payment $payment): void
    {
        $errorStatus = $this->configurator()->getStatusValue(PaymentTypeEnums::MANUAL, PaymentStateEnums::PENDING);

        if ($errorStatus) {
            $this->repository()->update($payment, [
                $this->configurator()->getStatusColumn() => $errorStatus
            ]);
        }
    }

    private function sendHttpRequest(GatewayRequest $gatewayRequest): Response
    {
        return Http::withHeaders($gatewayRequest->headers)
            ->send($gatewayRequest->method, $gatewayRequest->url, [$gatewayRequest->encoding => $gatewayRequest->payload]);
    }

    public function handleSuccessfulGatewayResponse(Payment $payment, PaymentPurchaseApiResponse $gatewayResponse): void
    {
        $lifetimeSeconds = $this->configurator()->getLinkLifetime($this->getGatewayName());

        $updateData = [
            $this->configurator()->getPageUrlColumn() => $gatewayResponse->page_url,
            $this->configurator()->getExternIdColumn() => $gatewayResponse->invoice_id,
            $this->configurator()->getExpiresAtColumn() => $lifetimeSeconds ? now()->addSeconds($lifetimeSeconds) : null,
        ];

        $this->repository()->update($payment, $updateData);
    }

    private function isValidGatewayResponse(PaymentPurchaseApiResponse $gatewayResponse): bool
    {
        if ($gatewayResponse->status === PaymentApiResponseStatusEnums::ERROR || empty($gatewayResponse->page_url)) {
            return false;
        }

        return true;
    }

    abstract public function buildApiRequest(PaymentPurchaseRequest $purchaseRequest, Payment $payment): GatewayRequest;

    abstract public function buildApiResponse(Response $httpResponse, Payment $payment): PaymentPurchaseApiResponse;
}