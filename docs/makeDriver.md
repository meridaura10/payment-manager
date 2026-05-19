# Створення нового драйвера для плітіжних систем

1. Створюємо клас та наслідуємось від AbstractDriver
2. Щоб подалі виокристовувати класи зручно через функції хелпери визначаємо для драйвера його можливості задопомогою інтерфейсів.
   SupportsChargeInterface, SupportsWebhookInterface, SupportsRecurringInterface
3. При виклику обробників напряму туди автоматично передасться назва драйвера або ж сам драйвер для вебхука
4. При створені класних обробників краще ознайомитись з кодом нище

```php
<?php

namespace App\Services\PaymentManager\PaymentDrivers\Monobank;


use Meridaura\PaymentManager\Drivers\AbstractCharge;use Meridaura\PaymentManager\Drivers\AbstractDriver;use Meridaura\PaymentManager\Drivers\AbstractRecurring;use Meridaura\PaymentManager\Drivers\AbstractWebhook;use Meridaura\PaymentManager\Drivers\Contracts\SupportsChargeInterface;use Meridaura\PaymentManager\Drivers\Contracts\SupportsRecurringInterface;use Meridaura\PaymentManager\Drivers\Contracts\SupportsWebhookInterface;

class MonobankDriver extends AbstractDriver implements SupportsChargeInterface, SupportsWebhookInterface, SupportsRecurringInterface
{
    public function __construct(
        protected array $config = []
    ) {
        конфіг сюди передається з того що було визначено в файлі кофігурації
        а також при виклику методів в paymentManager::purchse умовно
        $this->config['X-Token'] = $this->config['X-Token'] ?? або дефолтний; також можна визначати усі конфіги завдли тут
    }

    для recurring йде з пакету AbstractRecurring 
    public function recurring(): AbstractRecurring
    {
        return new MonobankRecurring($this->config);
    }

    для charge йде з пакету AbstractCharge 
    public function charge(): AbstractCharge
    {
        return new MonobankCharge($this->config);
    }

    для webhook йде з пакету AbstractWebhook 
    public function webhook(): AbstractWebhook
    {
        return new MonobankWebhook($this->config);
    }

    приклад додвання кастомного методу-обробника
    він буде доступний як і усі інші обробники але ide про нього не знає
    public function subscription(): MonobankSubscription
    {
        return new MonobankSubscription($this->config);
    }
    
    Щоб коректно працювали хуки в вебхуці важливо визначити кастомні обробники тут
    Ключ це те що ви вказали в config як як type значення сама функція обробника
    public function getCustomTypeClass(\UnitEnum|string $type): mixed
    {
        return match ($type) {
            Payment::TYPE_SUBSCRIPTION => $this->subscription(),
            default => null,
        };
    }

    public static function getGatewayName(): string
    {
        return 'monobank';
    }
}

```