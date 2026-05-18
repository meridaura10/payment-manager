<?php

namespace Meridaura\PaymentManager\DTO\Recurring\Setup;

use Meridaura\PaymentManager\Enums\PaymentApiResponseStatusEnum;

class RecurringSetupParseResponse
{
    public function __construct(
        readonly PaymentApiResponseStatusEnum $status,
        readonly string|int|null              $invoice_id = null,
        readonly ?string                      $page_url = null,
        readonly array                        $data = [],
        readonly bool                         $isReused = false,
    ) {
        //
    }
}