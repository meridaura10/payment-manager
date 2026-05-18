<?php

namespace Meridaura\PaymentManager\DTO\Recurring\Execute;

use Meridaura\PaymentManager\Enums\PaymentApiResponseStatusEnum;

class RecurringExecuteParseResponse
{
    public function __construct(
        readonly PaymentApiResponseStatusEnum $status,
        readonly string|int|null              $invoice_id = null,
        readonly ?string                      $page_url = null,
        readonly array                        $data = [],
    ) {
        //
    }
}