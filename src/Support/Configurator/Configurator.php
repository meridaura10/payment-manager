<?php

namespace Meridaura\PaymentManager\Support\Configurator;

use Illuminate\Support\Arr;
use Meridaura\PaymentManager\Enums\PaymentOperationEnum;
use Meridaura\PaymentManager\Enums\PaymentTypeEnum;
use Meridaura\PaymentManager\Enums\PaymentStageEnum;

class Configurator implements ConfiguratorInterface
{
    protected array $config = [];
    protected array $typeMap = [];
    protected array $operationMap = [];
    protected array $stageMap = [];

    public function __construct()
    {
        $this->config = config('payment-manager', []);

        $this->buildTypeMap();
        $this->buildOperationMap();
        $this->buildStageMap();
    }

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
        return Arr::get($this->config, 'database.columns.state');
    }

    public function getTypeColumn(): ?string
    {
        return Arr::get($this->config, 'database.columns.type');
    }

    public function getOperationColumn(): ?string
    {
        return Arr::get($this->config, 'database.columns.operation');
    }

    public function getWebhookModifyAtColumName(): ?string
    {
        return Arr::get($this->config, 'database.columns.webhook_modify_at');
    }

    public function getWebhookDataColumName(): ?string
    {
        return Arr::get($this->config, 'database.columns.webhook_data');
    }

    public function getResponseColumn(): ?string
    {
        return Arr::get($this->config, 'database.columns.response');
    }

    public function getTypeValue(\UnitEnum|string $key): ?string
    {
        return $this->findDbValueInMap($this->typeMap, $key);
    }
    
    public function getOperationValue(\UnitEnum|string $key): ?string
    {
        return $this->findDbValueInMap($this->operationMap, $key);
    }

    public function getStatusByStage(\UnitEnum|string $stage, \UnitEnum|string $type, \UnitEnum|string|null $operation = null): ?string
    {
        return $this->getCascadingConfig(
            'database.statuses',
            $this->nameOf($stage),
            $this->nameOf($type),
            $this->nameOf($operation)
        );
    }

    public function getDriverConfig(string $driverName): array
    {
        return Arr::get($this->config, "drivers.{$driverName}", []);
    }

    public function getLinkLifetime(string $driverName, \UnitEnum|string|null $type = null, \UnitEnum|string|null $operation = null): ?int
    {
        return $this->getCascadingConfig(
            "drivers.{$driverName}",
            'features.link_lifetime',
            $this->nameOf($type),
            $this->nameOf($operation),
            Arr::get($this->config, 'features.link_lifetime')
        );
    }

    public function getEventClass(string $eventName, \UnitEnum|string|null $type = null, \UnitEnum|string|null $operation = null): ?string
    {
        return $this->getCascadingConfig(
            'events',
            $eventName,
            $this->nameOf($type),
            $this->nameOf($operation)
        );
    }

    public function resolvePaymentType(string $key): \UnitEnum|string|null
    {
        return $this->typeMap[$key] ?? null;
    }

    public function resolveOperation(string $dbOperation): \UnitEnum|string|null
    {
        return $this->operationMap[$dbOperation] ?? null;
    }

    public function resolveStageFromStatus(string $dbStatus, \UnitEnum|string|null $type = null, \UnitEnum|string|null $operation = null): \UnitEnum|string|null
    {
        $typeStr = $this->nameOf($type);
        $operationStr = $this->nameOf($operation);

        if ($typeStr && $operationStr && isset($this->stageMap[$typeStr][$operationStr][$dbStatus])) {
            return $this->stageMap[$typeStr][$operationStr][$dbStatus];
        }

        if ($typeStr && isset($this->stageMap[$typeStr][$dbStatus])) {
            return $this->stageMap[$typeStr][$dbStatus];
        }

        return $this->stageMap['*'][$dbStatus] ?? null;
    }

    public function isSaveQuietlyEnabled(): bool
    {
        return (bool) Arr::get($this->config, 'features.save_quietly', false);
    }

    protected function getCascadingConfig(string $rootPath, string $key, ?string $typeStr = null, ?string $operationStr = null, mixed $default = null): mixed
    {
        $paths = array_filter([
            $typeStr && $operationStr ? "{$rootPath}.{$typeStr}.{$operationStr}.{$key}" : null,
            $typeStr ? "{$rootPath}.{$typeStr}.{$key}" : null,
            "{$rootPath}.{$key}"
        ]);

        foreach ($paths as $path) {
            if (Arr::has($this->config, $path)) {
                return Arr::get($this->config, $path);
            }
        }

        return $default;
    }

    protected function buildTypeMap(): void
    {
        $mapping = Arr::get($this->config, 'database.type_values', []);

        foreach ($mapping as $enumName => $dbValue) {
            $default = $enumName ?: $dbValue;

            if (isset($this->typeMap[$dbValue])) {
                throw new \InvalidArgumentException(
                    sprintf("Configuration error: Duplicate database value '%s' found in 'database.type_values'. Database mapping values must be strictly unique.", $dbValue)
                );
            }

            $this->typeMap[$dbValue] = $this->resolveEnum(PaymentTypeEnum::class, $enumName, $default);
        }
    }

    protected function buildOperationMap(): void
    {
        $mapping = Arr::get($this->config, 'database.operation_values', []);

        foreach ($mapping as $enumName => $dbValue) {
            if (isset($this->operationMap[$dbValue])) {
                throw new \InvalidArgumentException(
                    sprintf("Configuration error: Duplicate database value '%s' found in 'database.operation_values'. Database mapping values must be strictly unique.", $dbValue)
                );
            }

            $this->operationMap[$dbValue] = $this->resolveEnum(PaymentOperationEnum::class, $enumName, $dbValue);
        }
    }

    protected function buildStageMap(): void
    {
        $statuses = Arr::get($this->config, 'database.statuses', []);

        foreach ($statuses as $key => $value) {
            if (!is_array($value)) {
                $this->stageMap['*'][$value] = $this->resolveEnum(PaymentStageEnum::class, $key, $value);
                continue;
            }

            $type = $key;
            foreach ($value as $subKey => $subValue) {
                if (is_array($subValue)) {
                    $operation = $subKey;
                    foreach ($subValue as $stage => $status) {
                        $this->stageMap[$type][$operation][$status] = $this->resolveEnum(PaymentStageEnum::class, $stage, $status);
                    }
                } else {
                    $stage = $subKey;
                    $status = $subValue;
                    $this->stageMap[$type][$status] = $this->resolveEnum(PaymentStageEnum::class, $stage, $status);
                }
            }
        }
    }

    protected function findDbValueInMap(array $map, \UnitEnum|string $subject): ?string
    {
        $dbValue = array_search($subject, $map, true);

        if ($dbValue !== false) {
            return (string) $dbValue;
        }

        if (is_string($subject)) {
            foreach ($map as $dbKey => $mappedValue) {
                if ($mappedValue instanceof \UnitEnum && $mappedValue->name === $subject) {
                    return (string) $dbKey;
                }
            }
        }

        return null;
    }

    protected function resolveEnum(string $enumClass, string $enumName, mixed $default = null): \UnitEnum|string|null
    {
        return defined("{$enumClass}::{$enumName}") ? constant("{$enumClass}::{$enumName}") : $default;
    }

    protected function nameOf(\UnitEnum|string|null $value): ?string
    {
        return $value instanceof \UnitEnum ? $value->name : $value;
    }
}
