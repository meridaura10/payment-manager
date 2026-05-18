<?php

namespace Meridaura\PaymentManager\Support\PaymentRepository;

use Meridaura\PaymentManager\DTO\Charge\ChargePurchaseRequest;
use Meridaura\PaymentManager\DTO\Recurring\Execute\RecurringExecuteRequest;
use Meridaura\PaymentManager\DTO\Recurring\Setup\RecurringSetupRequest;
use Meridaura\PaymentManager\Models\Payment;

interface PaymentRepositoryInterface
{
    public function setCreatorUsing(\UnitEnum|array|string $type, \Closure $callback, \UnitEnum|string|array|null $operation = null): static;

    public function setDefaultCreator(\Closure $callback): static;

    public function resolvePayment(\UnitEnum|string $type, mixed $dto, array $paymentData = [], \UnitEnum|string|null $operation = null): Payment;

    public function findByExternId(string $value): ?Payment;

    public function getAttribute(Payment $payment, string $key, mixed $default = null): mixed;

    public function update(Payment $payment, array $attributes): void;
}