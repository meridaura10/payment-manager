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
Для кожного типу платежа можна визначити кастомні хуки до і після дефолтної обробки
хук до можна використати як валідатор якщо немає потрібних данних обробку можна зупинити кинувши помилку

ці хуки формуються динамічно 'webhook' + 'before|after' + 'type' + 'Handler';

хуки можна додати як до самого класу обробника вебхука так і до класу спеціалізованому на певному типу оплати
як приклад в класи MonobankRecurring|MonobankWebhook
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

можна визначити обробку вебхука в драйвері

якщо драйвер наслідує інтерфейс HandlesPaymentTypeWebhookInterface
тоді обробка вебхука переходить до нього 

йому будуть передані данні які визначення в методі parseRequestData


