# Створення нового драйвера для плітіжних систем

1. Створюємо клас та наслідуємось від AbstractDriver
2. Щоб подалі виокристовувати класи зручно через функції хелпери визначаємо для драйвера його можливості задопомогою інтерфейсів SupportsChargeInterface, SupportsWebhookInterface, SupportsRecurringInterface
3. При виклику стандартних функцій обробників від пакету туди буде автоматично передано назву драйвера
4. Якщо створенно кастомний обробник потипу subscription туди треба передавати назву самостійно як це показано нище

```php
<?php

namespace App\Services\PaymentManager\PaymentDrivers\Monobank;


use Meridaura\PaymentManager\Contracts\SupportsChargeInterface;
use Meridaura\PaymentManager\Contracts\SupportsRecurringInterface;
use Meridaura\PaymentManager\Contracts\SupportsWebhookInterface;
use Meridaura\PaymentManager\Drivers\AbstractCharge;
use Meridaura\PaymentManager\Drivers\AbstractDriver;
use Meridaura\PaymentManager\Drivers\AbstractRecurring;
use Meridaura\PaymentManager\Drivers\AbstractWebhook;

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

    // приклад додвання кастомного методу-обробника
    public function subscription(): MonobankSubscription
    {
        return (new MonobankSubscription($this->config))->setGatewayName($this->getGatewayName());
    }

    public static function getGatewayName(): string
    {
        return 'monobank';
    }
}

```