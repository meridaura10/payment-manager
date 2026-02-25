<?php

namespace Meridaura\PaymentManager\Drivers\Monobank\Validators;

use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Meridaura\PaymentManager\DTO\PaymentPurchaseRequestDTO;

class MonobankPurchaseValidator
{
    public static function validate(PaymentPurchaseRequestDTO $dto, array $config): array
    {
        return [
            'data' => static::validateData($dto, $config),
            'headers' => static::validateHeaders($dto, $config),
        ];
    }

    public static function validateData(PaymentPurchaseRequestDTO $dto, array $config): array
    {
        $data = [
            'amount' => (int) ($dto->amount * 100),
            'ccy' => $dto->currency,
            'merchantPaymInfo' => [
                'reference' => (string) $dto->orderId,
                'destination' => $dto->description,
            ],
            'webHookUrl' => $dto->webHookUrl ?? $config['webhook_url'] ?? null,
            'redirectUrl' => $dto->redirectUrl ?? $config['redirect_url'] ?? null,
        ];

        $validator = Validator::make($data, [
            'amount' => 'required|integer|min:10',
            'merchantPaymInfo.reference' => 'required|string',
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException('Error validate monobank data:' . $validator->errors()->first());
        }

        return $data;
    }

    public static function validateHeaders(PaymentPurchaseRequestDTO $dto, array $config): array
    {
        $data = $dto->headers;

        $validator = Validator::make($data, [
            'X-Token' => 'required|string',
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException('Error validate monobank headers: ' . $validator->errors()->first());
        }

        return $data;
    }
}