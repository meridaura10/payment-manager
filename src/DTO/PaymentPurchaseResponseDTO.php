<?php

namespace Meridaura\PaymentManager\DTO;

use Meridaura\PaymentManager\Enums\PaymentResponseStatusEnum;

class PaymentPurchaseResponseDTO
{
    public function __construct(
        readonly PaymentResponseStatusEnum $status,
        readonly string|int|null $extern_id = null,
        readonly ?string $page_url = null,
        readonly array $system_data = [],
        readonly ?PaymentErrorDTO $error = null,
    ) {

    }

    public function isSuccessful(): bool
    {
        return $this->status === PaymentResponseStatusEnum::SUCCESS;
    }
}