<?php

namespace Meridaura\PaymentManager\Contracts;

use Meridaura\PaymentManager\DTO\PaymentChargeRequestDTO;
use Meridaura\PaymentManager\DTO\PaymentChargeResponseDTO;

interface GatewayChargeInterface
{
    public function purchase(PaymentChargeRequestDTO $dataDto): PaymentChargeResponseDTO;
}