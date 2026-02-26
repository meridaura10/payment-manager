<?php

namespace Meridaura\PaymentManager\Drivers\Monobank\Validators;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Meridaura\PaymentManager\DTO\PaymentPurchaseRequestDTO;

class MonobankPurchaseValidator
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
            'headers' => $this->buildHeaders($validConfig),
            'payload' => $validPayload,
            'config' => $validConfig,
        ];
    }

    protected function validatePayload(): array
    {
        $data = [
            'amount' => $this->dto->amount,
            'ccy' => $this->dto->currency,
            'merchantPaymInfo' => [
                'reference' => (string) $this->dto->orderId,
                'destination' => $this->dto->description ?? "Оплата замовлення #{$this->dto->orderId}",
            ],
            'webHookUrl' => $this->dto->webHookUrl ?? $this->config['webhook_url'] ?? null,
            'redirectUrl' => $this->dto->redirectUrl ?? $this->config['redirect_url'] ?? null,
        ];

        $validator = Validator::make($data, [
            'amount' => 'required|integer|min:10',
            'merchantPaymInfo.reference' => 'required|string',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $data;
    }

    protected function validateConfig(): array
    {
        $validator = Validator::make($this->config, [
            'token' => 'required|string',
            'cms' => 'sometimes|string',
            'cms_version' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $this->config;
    }

    protected function buildHeaders(array $config): array
    {
        $headers = [
            'X-Token' => $config['token'],
        ];

        if (!empty($config['cms'])) {
            $headers['X-Cms'] = $config['cms'];
        }

        if (!empty($config['cms_version'])) {
            $headers['X-Cms-Version'] = $config['cms_version'];
        }

        return $headers;
    }
}