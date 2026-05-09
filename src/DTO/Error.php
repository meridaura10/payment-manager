<?php

namespace Meridaura\PaymentManager\DTO;

class Error
{
    public function __construct(
        public readonly string $message,
        public readonly ?string $systemMessage = null,
        public readonly ?string $gatewayMessage = null,
        public readonly string|int|null $code = null,
    ) {}
}