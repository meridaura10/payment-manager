<?php

namespace Meridaura\PaymentManager\Support\Configurator;

use Illuminate\Support\Arr;
use Meridaura\PaymentManager\Enums\PaymentTypeEnum;
use Meridaura\PaymentManager\Enums\PaymentStageEnum;

class Configurator implements ConfiguratorInterface
{
    protected array $config = [];

    public function __construct()
    {
        $this->config = config('payment-manager', []);

        foreach (PaymentTypeEnum::cases() as $type) {
            $statuses = \Illuminate\Support\Arr::get($this->config, "database.statuses.{$type->name}", []);
            $activeStatuses = array_filter($statuses);

            if (count($activeStatuses) !== count(array_unique($activeStatuses))) {
                throw new \RuntimeException("Payment Manager: Database statuses for PaymentTypeEnum::{$type->name} must be unique. Please check your configuration.");
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Database Mapping (Columns)
    |--------------------------------------------------------------------------
    */

    public function getPaymentModel(): ?string
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

    public function getStageColumn(): ?string
    {
        // Тепер посилаємось на 'state' у конфігу
        return Arr::get($this->config, 'database.columns.state');
    }

    public function getTypeColumn(): ?string
    {
        return Arr::get($this->config, 'database.columns.type');
    }

    public function getWebhookModifyAtColumName(): ?string
    {
        return Arr::get($this->config, "database.columns.webhook_modify_at");
    }

    public function getWebhookDataColumName(): ?string
    {
        return Arr::get($this->config, "database.columns.webhook_data");
    }

    public function getResponseColumn(): ?string
    {
        return Arr::get($this->config, "database.columns.response");
    }

    /*
    |--------------------------------------------------------------------------
    | Values Mapping
    |--------------------------------------------------------------------------
    */

    public function getTypeValue(string $key): ?string
    {
        return Arr::get($this->config, "database.type_values.{$key}");
    }

    public function getPaymentMethodValue(string $key): ?string
    {
        return Arr::get($this->config, "database.method_values.{$key}");
    }

    /**
     * Отримує рядок (status) для бази даних на основі етапу (stage)
     */
    public function getStatusByStage(PaymentTypeEnum $type, PaymentStageEnum $stage): ?string
    {
        return Arr::get($this->config, "database.statuses.{$type->name}.{$stage->name}");
    }

    /*
    |--------------------------------------------------------------------------
    | Drivers & Features
    |--------------------------------------------------------------------------
    */

    public function getDriverConfig(string $driverName): array
    {
        return Arr::get($this->config, "drivers.{$driverName}", []);
    }

    public function isReuseLinksEnabled(string $driverName): bool
    {
        $driverFeature = Arr::get($this->config, "drivers.{$driverName}.features.reuse_links");

        return is_null($driverFeature)
            ? (bool) Arr::get($this->config, 'features.reuse_links', false)
            : (bool) $driverFeature;
    }

    public function getLinkLifetime(string $driverName): ?int
    {
        $driverLifetime = Arr::get($this->config, "drivers.{$driverName}.features.link_lifetime");

        return is_null($driverLifetime)
            ? Arr::get($this->config, 'features.link_lifetime')
            : (int) $driverLifetime;
    }

    /*
    |--------------------------------------------------------------------------
    | Events & Enums Resolver
    |--------------------------------------------------------------------------
    */

    public function getEventClass(string $key): ?string
    {
        return Arr::get($this->config, "events.{$key}");
    }

    public function resolvePaymentType(string $key): ?PaymentTypeEnum
    {
        return $this->resolveEnumFromMapping(
            PaymentTypeEnum::class,
            'database.type_values',
            $key
        );
    }

    /**
     * Перетворює статус із бази (string) назад у внутрішній етап (stage)
     */
    public function resolveStageFromStatus(PaymentTypeEnum $type, string $dbStatus): ?PaymentStageEnum
    {
        return $this->resolveEnumFromMapping(
            PaymentStageEnum::class,
            "database.statuses.{$type->name}",
            $dbStatus
        );
    }

    public function isSaveQuietlyEnabled(): bool
    {
        return (bool) Arr::get($this->config, 'features.save_quietly', false);
    }

    protected function resolveEnumFromMapping(string $enumClass, string $configPath, string $key, mixed $default = null): mixed
    {
        $mapping = Arr::get($this->config, $configPath, []);
        $enumName = array_search($key, $mapping, true);

        if ($enumName && defined("{$enumClass}::{$enumName}")) {
            return constant("{$enumClass}::{$enumName}");
        }

        return $default;
    }
}