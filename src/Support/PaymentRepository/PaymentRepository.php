<?php

namespace Meridaura\PaymentManager\Support\PaymentRepository;

use Closure;
use Meridaura\PaymentManager\Enums\PaymentStageEnum;
use Meridaura\PaymentManager\Models\Payment;
use Meridaura\PaymentManager\Support\EventManager\EventManagerInterface;
use RuntimeException;
use Meridaura\PaymentManager\DTO\PaymentPurchaseRequest;
use Meridaura\PaymentManager\Support\Configurator\ConfiguratorInterface;

class PaymentRepository implements PaymentRepositoryInterface
{
    protected ?Closure $purchaseCreator = null;
    protected ?Closure $recurringCreator = null;

    public function __construct(
        protected ConfiguratorInterface $configurator,
    ) {}

    /**
     * Встановлює логіку створення нового платежу.
     *
     * @param Closure(PaymentPurchaseRequest $dto, string $gateway): mixed $callback
     * @return static
     */
    public function createPurchaseUsing(Closure $callback): static
    {
        $this->purchaseCreator = $callback;

        return $this;
    }

    public function createRecurringUsing(Closure $callback): static
    {
        $this->recurringCreator = $callback;

        return $this;
    }

    public function createPaymentPurchase(PaymentPurchaseRequest $dto, array $paymentData = []): Payment
    {
        if (!$this->purchaseCreator) {
            throw new RuntimeException(
                'Логіка створення платежу не визначена. Використайте PaymentRepository::createPurchaseUsing() у вашому ServiceProvider.'
            );
        }

        /* @var $paymentModel Payment */
        $paymentModel = ($this->purchaseCreator)($dto, $paymentData);

        if ($paymentModel->wasRecentlyCreated) {
            $this->events()->dispatchLifecycleStage($paymentModel, PaymentStageEnum::CREATED);
        }

        return $paymentModel;
    }

    public function getModel(): Payment
    {
         return new ($this->configurator->getPaymentModel());
    }

    public function findByExternId(string $value): ?Payment
    {
        $model = $this->getModel();
        $column = $this->configurator->getExternIdColumn();

        return $model::query()->where($column, $value)->first();
    }

    public function update(Payment $payment, array $attributes): void
    {
        $validAttributes = array_filter(
            $attributes,
            fn($key) => !empty($key) && is_string($key),
            ARRAY_FILTER_USE_KEY
        );

        if (empty($validAttributes)) {
            return;
        }

        $statusColumn = $this->configurator->getStageColumn();
        $statusChanged = false;
        $oldStatus = null;
        $newStatus = null;

        if (array_key_exists($statusColumn, $validAttributes)) {
            $oldStatus = $payment->getAttribute($statusColumn);
            $newStatus = $validAttributes[$statusColumn];

            if ($oldStatus !== $newStatus) {
                $statusChanged = true;
            }
        }

        $payment->forceFill($validAttributes);

        if ($this->configurator->isSaveQuietlyEnabled()) {
            $payment->saveQuietly();
        } else {
            $payment->save();
        }

        if ($statusChanged) {
            $this->events()->dispatchChangeStatus($payment, $newStatus, $oldStatus);
        }
    }
    public function getAttribute(Payment $payment, string $key, mixed $default = null): mixed
    {
        $dotNotationPath = str_replace('->', '.', $key);

        return data_get($payment, $dotNotationPath, $default);
    }

    public function events(): EventManagerInterface
    {
        return app(EventManagerInterface::class);
    }
}