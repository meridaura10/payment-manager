<?php

namespace Meridaura\PaymentManager\Drivers\NovaPay\Validators;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Meridaura\PaymentManager\DTO\PaymentPurchaseRequestDTO;
use Meridaura\PaymentManager\Support\RsaSigner;

class NovaPayPurchaseValidator
{
    public function __construct(
        protected PaymentPurchaseRequestDTO $dto,
        protected array $config = [],
    ) {
    }

    public function validate(): array
    {
        $validConfig = $this->validateConfig();
        $validPayload = $this->validatePayload();

        return [
            'headers' => $this->buildHeaders($validConfig, $validPayload),
            'payload' => $validPayload,
            'config'  => $validConfig,
        ];
    }

    protected function validatePayload(): array
    {
        $data = [
            [
                'session_id' => $this->dto->orderId,
                'amount' => $this->dto->amount,
                'currency' => $this->dto->currency,
                'description' => $this->dto->description ?? "Оплата замовлення #{$this->dto->orderId}",
                'success_url' => $this->dto->redirectUrl ?? $this->config['success_url'] ?? null,
            ]
        ];

        $validator = Validator::make($data[0], [
            'session_id' => 'required|string',
            'amount' => 'required|numeric|min:0.1',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $data;
    }

    protected function validateConfig(): array
    {
        $validator = Validator::make($this->config, [
            'merchant_id' => 'required|string',
            'private_key' => 'required|string',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $this->config;
    }

    protected function buildHeaders(array $config, array $payload): array
    {
        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $signature = RsaSigner::sign($jsonPayload, $config['private_key']);

        return [
            'Content-Type'  => 'application/json',
            'x-merchant-id' => $config['merchant_id'],
            'x-sign' => $signature,
        ];
    }
}