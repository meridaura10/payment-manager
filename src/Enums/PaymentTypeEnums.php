<?php

namespace Meridaura\PaymentManager\Enums;

enum PaymentTypeEnums: string
{
    case MANUAL = 'manual';

    case RECURRING = 'recurring';
}
