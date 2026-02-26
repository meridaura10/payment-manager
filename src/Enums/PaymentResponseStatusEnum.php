<?php

namespace Meridaura\PaymentManager\Enums;

enum PaymentResponseStatusEnum: string
{
    case SUCCESS = 'success';
    case ERROR = 'error';
    case PENDING = 'pending';

    case VALIDATION_ERROR = 'validation_error';
}
