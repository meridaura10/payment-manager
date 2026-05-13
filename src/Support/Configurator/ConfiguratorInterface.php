<?php

namespace Meridaura\PaymentManager\Support\Configurator;

use Meridaura\PaymentManager\Enums\PaymentStageEnum;
use Meridaura\PaymentManager\Enums\PaymentTypeEnum;

interface ConfiguratorInterface
{
    public function getPaymentModel(): ?string;
    public function getPaymentGatewayColumn(): ?string;
    public function getExternIdColumn(): ?string;
    public function getPageUrlColumn(): ?string;
    public function getExpiresAtColumn(): ?string;
    public function getStageColumn(): ?string;
    public function getTypeColumn(): ?string;
    public function getWebhookModifyAtColumName(): ?string;
    public function getWebhookDataColumName(): ?string;
    public function getResponseColumn(): ?string;
    public function getTypeValue(string $key): ?string;
    public function getPaymentMethodValue(string $key): ?string;
    public function getStatusByStage(PaymentTypeEnum $type, PaymentStageEnum $stage): ?string;
    public function getDriverConfig(string $driverName): array;
    public function isReuseLinksEnabled(string $driverName): bool;
    public function getLinkLifetime(string $driverName): ?int;
    public function getEventClass(string $key): ?string;
    public function resolvePaymentType(string $key): ?PaymentTypeEnum;
    public function resolveStageFromStatus(PaymentTypeEnum $type, string $dbStatus): ?PaymentStageEnum;
    public function isSaveQuietlyEnabled(): bool;
}