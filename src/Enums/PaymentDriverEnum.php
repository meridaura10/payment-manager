<?php

namespace Meridaura\PaymentManager\Enums;

enum PaymentDriverEnum: string
{
    case MONOBANK = 'monobank';

    case LIQPAY = 'liqpay';

    case STRIPE = 'stripe';

    case WAYFORPAY = 'wayforpay';
}
