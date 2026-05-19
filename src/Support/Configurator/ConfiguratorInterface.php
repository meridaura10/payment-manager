<?php

namespace Meridaura\PaymentManager\Support\Configurator;

interface ConfiguratorInterface
{
    public function getPaymentModel(): ?string;

    public function getPaymentGatewayColumn(): ?string;

    public function getExternIdColumn(): ?string;

    public function getPageUrlColumn(): ?string;

    public function getExpiresAtColumn(): ?string;

    public function getStageColumn(): ?string;

    public function getTypeColumn(): ?string;

    public function getOperationColumn(): ?string;

    public function getWebhookModifyAtColumName(): ?string;

    public function getWebhookDataColumName(): ?string;

    public function getResponseColumn(): ?string;

    public function getTypeValue(string $key): ?string;

    public function getStatusByStage(\UnitEnum|string $stage, \UnitEnum|string $type, \UnitEnum|string|null $operation = null): ?string;

    public function getDriverConfig(string $driverName): array;

    public function getLinkLifetime(string $driverName, \UnitEnum|string|null $type = null, \UnitEnum|string|null $operation = null): ?int;

    public function getEventClass(string $eventName, \UnitEnum|string|null $type = null, \UnitEnum|string|null $operation = null): ?string;

    public function resolvePaymentType(string $key): \UnitEnum|string|null;

    public function resolveStageFromStatus(string $dbStatus, \UnitEnum|string|null $type = null, \UnitEnum|string|null $operation = null): \UnitEnum|string|null;

    public function getOperationValue(string $key): ?string;

    public function resolveOperation(string $dbOperation): \UnitEnum|string|null;

    public function isSaveQuietlyEnabled(): bool;
}