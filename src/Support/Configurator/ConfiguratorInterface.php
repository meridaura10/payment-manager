<?php

namespace Meridaura\PaymentManager\Support\Configurator;

use Meridaura\PaymentManager\Enums\PaymentTypeEnums;
use Meridaura\PaymentManager\Enums\PaymentStateEnums;

interface ConfiguratorInterface
{
    public function getPaymentModel(): string;

    public function getEventClass(string $key): ?string;

    public function getPaymentMethodValue(string $key): ?string;

    public function getPaymentGatewayColumn(): ?string;

    public function setEvent(string $abstract, string $event): static;

    public function getDriverConfig(string $driverName): array;

    public function getExternIdColumn(): ?string;

    public function getPageUrlColumn(): ?string;

    public function getExpiresAtColumn(): ?string;

    public function isReuseLinksEnabled(string $driverName): bool;

    public function getLinkLifetime(string $driverName): ?int;

    public function getStatusColumn(): ?string;

    public function getTypeValue(string $key): ?string;

    public function getStatusValue(PaymentTypeEnums $type, PaymentStateEnums $stage): ?string;

    public function getWebhookModifyAtColumName(): string;

    public function getWebhookDataColumName(): string;
}