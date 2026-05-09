<?php

namespace Meridaura\PaymentManager\DTO;

use Carbon\Carbon;
use Meridaura\PaymentManager\Enums\PaymentStateEnums;
use Meridaura\PaymentManager\Enums\WebhookParseStatusEnums;

class WebhookParseData
{
    public function __construct(
        public readonly string|int|null $externId,
        public readonly ?PaymentStateEnums $status,
        public readonly ?Carbon $modifiedDate = null,
        public readonly array $fullRequestData = [],
    ) {

    }
}