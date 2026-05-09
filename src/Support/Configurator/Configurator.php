<?php

namespace Meridaura\PaymentManager\Support\Configurator;

use Illuminate\Support\Arr;
use Meridaura\PaymentManager\Enums\PaymentTypeEnums;
use Meridaura\PaymentManager\Enums\PaymentStateEnums;

class Configurator implements ConfiguratorInterface
{
    protected array $config = [];

    public function __construct()
    {
        // Завжди краще ставити [] як fallback, якщо конфіг раптом не знайдено
        $this->config = config('payment-manager', []);
    }

    /*
    |--------------------------------------------------------------------------
    | Database Mapping (Колонки)
    |--------------------------------------------------------------------------
    */

    public function getPaymentModel(): string
    {
        return Arr::get($this->config, 'model');
    }

    public function getPaymentGatewayColumn(): ?string
    {
        return Arr::get($this->config, 'database.columns.gateway');
    }

    public function getExternIdColumn(): ?string
    {
        return Arr::get($this->config, 'database.columns.extern_id');
    }

    public function getPageUrlColumn(): ?string
    {
        return Arr::get($this->config, 'database.columns.page_url');
    }

    public function getExpiresAtColumn(): ?string
    {
        return Arr::get($this->config, 'database.columns.expires_at');
    }

    public function getStatusColumn(): ?string
    {
        return Arr::get($this->config, 'database.columns.status');
    }

    public function getTypeColumn(): ?string
    {
        return Arr::get($this->config, 'database.columns.type');
    }

    public function getTypeValue(string $key): ?string
    {
        return Arr::get($this->config, "database.type_values.{$key}");
    }

    /*
    |--------------------------------------------------------------------------
    | Database Mapping (Значення)
    |--------------------------------------------------------------------------
    */

    public function getPaymentMethodValue(string $key): ?string
    {
        return Arr::get($this->config, "database.method_values.{$key}");
    }

    /*
    |--------------------------------------------------------------------------
    | Drivers & Features (Драйвери та Фічі)
    |--------------------------------------------------------------------------
    */

    public function getDriverConfig(string $driverName): array
    {
        return Arr::get($this->config, "drivers.{$driverName}", []);
    }

    /**
     * Розумна перевірка: спочатку в драйвері, потім глобально
     */
    public function isReuseLinksEnabled(string $driverName): bool
    {
        // 1. Шукаємо специфічне налаштування для конкретного драйвера
        $driverFeature = Arr::get($this->config, "drivers.{$driverName}.features.reuse_links");

        if (!is_null($driverFeature)) {
            return (bool) $driverFeature;
        }

        // 2. Якщо в драйвері null (не вказано), беремо глобальне
        return (bool) Arr::get($this->config, 'features.reuse_links', false);
    }

    /**
     * Отримує час життя посилання на оплату (у секундах).
     * Спочатку перевіряє налаштування драйвера, потім глобальні.
     */
    public function getLinkLifetime(string $driverName): ?int
    {
        // 1. Шукаємо специфічне налаштування для конкретного драйвера
        $driverLifetime = Arr::get($this->config, "drivers.{$driverName}.features.link_lifetime");

        if (!is_null($driverLifetime)) {
            return (int) $driverLifetime;
        }

        // 2. Якщо в драйвері null, беремо глобальне значення (fallback: 3600 секунд)
        return Arr::get($this->config, 'features.link_lifetime');
    }

    /*
    |--------------------------------------------------------------------------
    | Events (Події)
    |--------------------------------------------------------------------------
    */

    public function getEventClass(string $key): ?string
    {
        // У новому конфігу події лежать прямо за ключем (без вкладеності .class)
        return Arr::get($this->config, "events.{$key}");
    }

    public function setEvent(string $key, ?string $eventClass): static
    {
        // Використовуємо Arr::set, щоб безпечно перезаписати значення в масиві
        Arr::set($this->config, "events.{$key}", $eventClass);

        return $this;
    }

    public function getStatusValue(PaymentTypeEnums $type, PaymentStateEnums $stage): ?string
    {
        return Arr::get($this->config, "statuses.{$type->value}.{$stage->value}");
    }

    public function getWebhookModifyAtColumName(): string
    {
        return Arr::get($this->config, "database.columns.webhook_modify_at");
    }

    public function getWebhookDataColumName(): string
    {
        return Arr::get($this->config, "database.columns.webhook_data");
    }
}