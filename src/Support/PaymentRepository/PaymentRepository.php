<?php

namespace Meridaura\PaymentManager\Support\PaymentRepository;

use Closure;
use Meridaura\PaymentManager\Models\Payment;
use RuntimeException;
use Meridaura\PaymentManager\DTO\PaymentPurchaseRequest;
use Meridaura\PaymentManager\Support\Configurator\ConfiguratorInterface;

class PaymentRepository implements PaymentRepositoryInterface
{
    protected ?Closure $purchaseCreator = null;
    protected ?Closure $recurringCreator = null;

    public function __construct(
        protected ConfiguratorInterface $configurator
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
        // Якщо користувач не налаштував замикання — жорстко зупиняємо процес
        if (!$this->purchaseCreator) {
            throw new RuntimeException(
                'Логіка створення платежу не визначена. Використайте PaymentRepository::createPurchaseUsing() у вашому ServiceProvider.'
            );
        }

        // Викликаємо користувацьку логіку
        $paymentModel = ($this->purchaseCreator)($dto, $paymentData);

        // Викликаємо подію через конфігуратор
        $this->fireEvent('paymentCreate', $paymentModel);

        return $paymentModel;
    }

    protected function fireEvent(string $eventKey, mixed $payload): void
    {
        // Отримуємо клас події через конфігуратор
        $eventClass = $this->configurator->getEventClass($eventKey);

        if ($eventClass && class_exists($eventClass)) {
            event(new $eventClass($payload));
        }
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
        foreach ($attributes as $key => $value) {
            $payment->setAttribute($key, $value);
        }

        $payment->save();

        $this->fireEvent('paymentUpdate', $payment);
    }
}