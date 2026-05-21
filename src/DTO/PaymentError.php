<?php

namespace Meridaura\PaymentManager\DTO;

use Illuminate\Contracts\Support\Arrayable;

class PaymentError implements Arrayable
{
    public function __construct(
        public readonly string $message,
        public readonly ?string $systemMessage = null,
        public readonly ?string $gatewayMessage = null,
        public readonly string|int|null $code = null,
    ) {}


    public function toArray(): array
    {
        return array_filter([
            'message' => $this->message,
            'system_message' => $this->systemMessage,
            'gateway_message' => $this->gatewayMessage,
            'code' => $this->code,
        ]);
    }
}