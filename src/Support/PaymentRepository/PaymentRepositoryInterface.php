<?php

namespace Meridaura\PaymentManager\Support\PaymentRepository;

use Meridaura\PaymentManager\DTO\PaymentPurchaseRequest;
use Meridaura\PaymentManager\Models\Payment;

interface PaymentRepositoryInterface
{
    /**
     * @param Closure(PaymentPurchaseRequest $dto, string $gateway): mixed $callback
     */
    public function createPurchaseUsing(\Closure $callback): static;

    public function createRecurringUsing(\Closure $callback): static;

    public function createPaymentPurchase(PaymentPurchaseRequest $dto, array $paymentData = []): Payment;

    public function findByExternId(string $value): ?Payment;

    public function update(Payment $payment, array $attributes): void;
}