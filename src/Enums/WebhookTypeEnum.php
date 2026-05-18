<?php

namespace Meridaura\PaymentManager\Enums;

enum WebhookTypeEnum
{
    case CHARGE;

    case RECURRING;
}
