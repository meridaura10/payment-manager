<?php

namespace Meridaura\PaymentManager\Drivers;

use Meridaura\PaymentManager\DTO\Cash\CashPayRequest;
use Meridaura\PaymentManager\DTO\Cash\CashPayResponse;
use Meridaura\PaymentManager\Enums\PaymentStageEnum;
use Meridaura\PaymentManager\Enums\PaymentTypeEnum;
use Meridaura\PaymentManager\Models\Payment;
use Meridaura\PaymentManager\Traits\UseCoreTrait;

abstract class AbstractCash
{
    use UseCoreTrait;

    private ?string $gatewayName = null;

    protected \UnitEnum|string $paymentType = PaymentTypeEnum::CASH;

    public function pay(CashPayRequest $request): CashPayResponse
    {
        if ($request->gateway) {
            $this->setGatewayName($request->gateway);
        }

        if ($request->type) {
            $this->paymentType = $request->type;
        }

        $paymentData = array_merge(
            $request->paymentData,
            $this->getCreatePaymentData(),
        );

        $payment = $this->repository()->resolvePayment($request->type, $request, $paymentData);

        $this->afterPayment($request, $payment);

        return new CashPayResponse($request, $payment);
    }

    abstract public function afterPayment(CashPayRequest $request, Payment $payment): void;

    protected function getCreatePaymentData(): array
    {
        $typeStr = $this->paymentType instanceof \UnitEnum ? $this->paymentType->name : $this->paymentType;
        $type = $this->configurator()->resolvePaymentType($typeStr);

        return [
            $this->configurator()->getTypeColumn() => $type ? $this->configurator()->getTypeValue($type) : $typeStr,
            $this->configurator()->getPaymentGatewayColumn() => $this->getGatewayName(),
            $this->configurator()->getStageColumn() => $this->configurator()->getStatusByStage(PaymentStageEnum::CREATED, $typeStr),
        ];
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