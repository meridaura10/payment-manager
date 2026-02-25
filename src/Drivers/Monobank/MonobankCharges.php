<?php

namespace Meridaura\PaymentManager\Drivers\Monobank;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Meridaura\PaymentManager\Contracts\GatewayChargeInterface;
use Meridaura\PaymentManager\DTO\PaymentPurchaseRequestDTO;
use Meridaura\PaymentManager\DTO\PaymentPurchaseResponseDTO;
use Meridaura\PaymentManager\Drivers\Monobank\Validators\MonobankPurchaseValidator;

class MonobankCharges implements GatewayChargeInterface
{
    public function __construct(protected array $config = [])
    {
    }

    public function purchase(PaymentPurchaseRequestDTO $dataDto): PaymentPurchaseResponseDTO
    {
        $data = MonobankPurchaseValidator::validate($dataDto, $this->config);

        try {
            $response = Http::withHeaders($data['headers'])
                ->baseUrl($this->config['base_url'])
                ->post('merchant/invoice/create', $data['data']);


            if ($response->failed()) {
                throw new \Exception('Monobank API Error: ' . $response->body());
            }

            $responseData = $response->json();

            return new PaymentPurchaseResponseDTO(
                status: 'ok',
                system_data: $responseData,
            );

        } catch (\Exception $exception) {
            Log::error('PaymentManager [Monobank] Error: ' . $exception->getMessage(), [
                'order_id' => $dataDto->orderId,
                'data' => $data
            ]);

            return new PaymentPurchaseResponseDTO(
                status: 'error',
                errors: [
                    'message' => $exception->getMessage(),
                ]
            );
        }
    }
}