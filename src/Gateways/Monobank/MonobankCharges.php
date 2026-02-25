<?php

namespace Meridaura\PaymentManager\Gateways\Monobank;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Meridaura\PaymentManager\Contracts\GatewayChargeInterface;
use Meridaura\PaymentManager\DTO\PaymentChargeRequestDTO;
use Meridaura\PaymentManager\DTO\PaymentChargeResponseDTO;

class MonobankCharges implements GatewayChargeInterface
{
    public function __construct(protected array $config = [])
    {

    }

    public function purchase(PaymentChargeRequestDTO $dataDto): PaymentChargeResponseDTO
    {
        $monoData = [
            'amount' => intval($dataDto->amount * 100),
            'ccy' => $dataDto->currency,
            'webHookUrl' => $dataDto->webHookUrl,
            'redirectUrl' => $dataDto->redirectUrl,
            'merchantPaymInfo' => $dataDto->driverOptions['merchantPaymInfo'],
        ];

        try {
            $response = Http::withHeaders($dataDto->headers)->post($this->apiUrl, $data);

            $responseData = json_decode($response, true);

            if (empty($responseData['invoiceId']) || empty($responseData['pageUrl'])) {
                Log::error(__METHOD__ . ' ' . json_encode($responseData, JSON_UNESCAPED_UNICODE));

                return false;
            }

            $payment->setAttribute('extern_id', $responseData['invoiceId']);
            $payment->save();

            return $responseData['pageUrl'];

        } catch (\Exception $exception) {
            Log::error(__METHOD__ . ' ' . $exception->getMessage());
        }

        return false;
    }
}