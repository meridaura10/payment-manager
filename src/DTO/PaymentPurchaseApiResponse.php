<?php

namespace Meridaura\PaymentManager\DTO;

use Meridaura\PaymentManager\Enums\PaymentApiResponseStatusEnums;

class PaymentPurchaseApiResponse
{
    public function __construct(
        readonly PaymentApiResponseStatusEnums $status,
        readonly string|int|null $invoice_id = null,
        readonly ?string $page_url = null,
        readonly array $data = [],
    ) {
        //
    }
}