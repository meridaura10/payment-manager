<?php

namespace Meridaura\PaymentManager\Drivers\NovaPay;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Meridaura\PaymentManager\Contracts\GatewayChargeInterface;
use Meridaura\PaymentManager\DTO\PaymentPurchaseRequestDTO;
use Meridaura\PaymentManager\DTO\PaymentPurchaseResponseDTO;
use Meridaura\PaymentManager\DTO\PaymentErrorDTO;
use Meridaura\PaymentManager\Drivers\NovaPay\Validators\NovaPayPurchaseValidator;
use Meridaura\PaymentManager\Enums\PaymentResponseStatusEnum;

class NovaPayCharges implements GatewayChargeInterface
{
    public function __construct(
        protected array $config = []
    ) {}

    public function purchase(PaymentPurchaseRequestDTO $dataDto): PaymentPurchaseResponseDTO
    {
        try {
            $validate = (new NovaPayPurchaseValidator($dataDto, $this->config))->validate();

            $response = Http::withHeaders($validate['headers'])
                ->baseUrl($this->config['base_url'])
                ->post('payment', $validate['payload']);

            $responseData = $response->json() ?? [];

            if (!$response->successful()) {
                return new PaymentPurchaseResponseDTO(
                    status: PaymentResponseStatusEnum::ERROR,
                    error: new PaymentErrorDTO(
                        message: $responseData['message'] ?? 'Unknown NovaPay API Error',
                        code: $responseData['code'] ?? 'api_error',
                        httpStatus: $response->status(),
                    ),
                );
            }

            return new PaymentPurchaseResponseDTO(
                status: PaymentResponseStatusEnum::SUCCESS,
                extern_id: $responseData['id'] ?? null,
                page_url: $responseData['url'] ?? null,
                system_data: $responseData,
            );

        } catch (ValidationException $exception) {
            return new PaymentPurchaseResponseDTO(
                status: PaymentResponseStatusEnum::VALIDATION_ERROR,
                error: new PaymentErrorDTO(
                    message: $exception->getMessage(),
                    code: 422,
                    validations: $exception->errors(),
                ),
            );

        } catch (\Throwable $exception) {
            return new PaymentPurchaseResponseDTO(
                status: PaymentResponseStatusEnum::ERROR,
                error: new PaymentErrorDTO(
                    message: 'System error: ' . $exception->getMessage(),
                    code: $exception->getCode() ?: 500,
                ),
            );
        }
    }
}