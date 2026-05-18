<?php

namespace Meridaura\PaymentManager\DTO\Webhook;

use Carbon\Carbon;
use Meridaura\PaymentManager\Enums\PaymentStageEnum;

class WebhookParseData
{
    public function __construct(
        public readonly string|int|null   $externId,
        public readonly ?PaymentStageEnum $stage,
        public readonly ?Carbon           $modifiedDate = null,
        public readonly array             $fullRequestData = [],
    ) {

    }
}