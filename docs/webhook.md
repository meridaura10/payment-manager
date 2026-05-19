# Обробка вебхука
```php
<?php

namespace App\Services\PaymentManager\PaymentDrivers\Monobank;

use Carbon\Carbon;
use Meridaura\PaymentManager\Drivers\AbstractWebhook;
use Meridaura\PaymentManager\DTO\Webhook\WebhookParseData;
use Meridaura\PaymentManager\Enums\PaymentStageEnum;

class MonobankWebhook extends AbstractWebhook
{
    public function __construct(
        protected array $config = []
    ) {

    }

    public function parseRequestData(array $allRequestData = []): WebhookParseData
    {
        $status = $allRequestData['status'] ?? null;

        коли ми повертаємо тип наприклад PAID буде викликано евент який визначений в конфігу для цього типу та операції
        в визначення поле запишеться той статус який був опреділений в конфігу
        $stage = match ($status) {
            'created', 'processing' => PaymentStageEnum::PENDING,
            'success' => PaymentStageEnum::PAID,
            'failure' => PaymentStageEnum::FAILED,
            default => null,
        };

        return new WebhookParseData(
            externId: $allRequestData['invoiceId'] ?? null,
            stage: $stage,
            modifiedDate: Carbon::parse($allRequestData['modifiedDate']) ?? now(),
            fullRequestData: $allRequestData,
        );
    }
}
```

# Хуки під час обробки
Для кожного типу платежу можна визначити власні (кастомні) хуки, які спрацьовуватимуть до та після дефолтної обробки.

Хук «до» (before) зручно використовувати як валідатор. Якщо в запиті бракує потрібних даних, подальшу обробку можна зупинити, повернувши об'єкт помилки.

Назви методів формуються динамічно за шаблоном: 'webhook' + 'Before|After' + 'Type' + 'Handler' (наприклад, webhookBeforeChargeHandler).

Де розмістити? Ці хуки можна додати як до основного класу обробника вебхуків (наприклад, MonobankWebhook), так і до спеціалізованого класу конкретного типу платежу (наприклад, MonobankRecurring).

```php
    public function webhookBeforeRecurringHandler(Payment $payment, WebhookParseData $data, $operation = null): ?Error
    {
        if (($data->fullRequestData['payMethod'] ?? null) !== 'pan') {
            return null;
        }

        if ($data->stage === PaymentStageEnum::PAID && !isset($data->fullRequestData['walletData']['cardToken'])) {
            return new Error('waiting_for_wallet_data', 'Not found wallet data yet.');
        }

        return null;
    }

    public function webhookAfterRecurringHandler(Payment $payment, WebhookParseData $data, $operation = null): ?Error
    {
        if ($data->fullRequestData['walletData']['cardToken'] ?? null) {
            $payment->update([
                'recurring_data' => $data->fullRequestData['walletData']
            ]);
        }

        return null;
    }
```

# Якщо дефолтна обробка не підходить

Ви можете делегувати логіку обробки вебхука самому платіжному драйверу. Якщо клас вашого драйвера імплементує інтерфейс HandlesPaymentTypeWebhookInterface, пакет автоматично переведе весь контроль на нього.

У метод обробки драйвера будуть передані вже підготовлені дані (DTO об'єкт), які були попередньо сформовані вашим методом parseRequestData.


