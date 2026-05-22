<?php

namespace Meridaura\PaymentManager\Enums;

enum PaymentTypeEnum
{
    case CHARGE;

    case RECURRING;

    case CASH;
}
