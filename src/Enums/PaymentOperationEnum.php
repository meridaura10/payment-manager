<?php

namespace Meridaura\PaymentManager\Enums;

enum PaymentOperationEnum
{
    case CHARGE_PURCHASE;

    case RECURRING_SETUP;

    case RECURRING_EXECUTE;
}
