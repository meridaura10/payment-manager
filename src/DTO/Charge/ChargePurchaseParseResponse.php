<?php

namespace Meridaura\PaymentManager\DTO\Charge;

use Meridaura\PaymentManager\Enums\PaymentApiResponseStatusEnum;

class ChargePurchaseParseResponse
{
    public function __construct(
        readonly PaymentApiResponseStatusEnum $status,
        readonly string|int|null $invoice_id = null,
        readonly ?string $page_url = null,
        readonly mixed $html_form = null,
        readonly array $data = [],
        readonly bool $isReused = false,
    ) {
        //
    }
}