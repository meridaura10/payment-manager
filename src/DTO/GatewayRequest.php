<?php

namespace Meridaura\PaymentManager\DTO;

class GatewayRequest
{
    public function __construct(
        public readonly string $url,
        public readonly array $payload = [],
        public readonly array $headers = [],
        public readonly string $method = 'Post',
        public readonly string $encoding = 'json',
    ) {}
}