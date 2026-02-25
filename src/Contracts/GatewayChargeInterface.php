<?php

namespace Meridaura\PaymentManager\Contracts;

use Meridaura\PaymentManager\DTO\PaymentPurchaseRequestDTO;
use Meridaura\PaymentManager\DTO\PaymentPurchaseResponseDTO;

interface GatewayChargeInterface
{
    public function purchase(PaymentPurchaseRequestDTO $dataDto): PaymentPurchaseResponseDTO;
}