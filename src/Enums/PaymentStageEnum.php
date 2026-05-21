<?php

namespace Meridaura\PaymentManager\Enums;

enum PaymentStageEnum
{
    case CREATED;
    case PENDING;
    case PAID;
    case FAILED;
}
