<?php

namespace Meridaura\PaymentManager\Drivers;

use Meridaura\PaymentManager\DTO\Cash\CashPayRequest;
use Meridaura\PaymentManager\Enums\PaymentStageEnum;
use Meridaura\PaymentManager\Enums\PaymentTypeEnum;
use Meridaura\PaymentManager\Models\Payment;
use Meridaura\PaymentManager\Traits\UseCoreTrait;

abstract class AbstractCash
{
    use UseCoreTrait;

    public function pay(CashPayRequest $request): void
    {
        $paymentData = array_merge(
            $request->paymentData,
            $this->getCreatePaymentData($request->type, $request->gateway),
        );

        $payment = $this->repository()->resolvePayment($request->type, $request, $paymentData);

        $this->afterPayment($request, $payment);
    }

    abstract public function afterPayment(CashPayRequest $request, Payment $payment): void;

    protected function getCreatePaymentData(\UnitEnum|string $type, \UnitEnum|string $gateway): array
    {
        $typeStr = $type instanceof \UnitEnum ? $type->name : $type;
        $gatewayStr = $gateway instanceof \UnitEnum ? $gateway->name : $gateway;

        return [
            $this->configurator()->getTypeColumn() => $this->configurator()->getTypeValue($typeStr),
            $this->configurator()->getPaymentGatewayColumn() => $gatewayStr,
            $this->configurator()->getStageColumn() => $this->configurator()->getStatusByStage(PaymentStageEnum::CREATED, $type),
        ];
    }
}