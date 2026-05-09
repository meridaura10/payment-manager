<?php

namespace Meridaura\PaymentManager\Enums;

enum PaymentStateEnums: string
{
    case CREATED  = 'created';   // Тільки-но створено в базі (ще без лінка)
    case PENDING  = 'pending';   // Отримали лінк на оплату, чекаємо дій клієнта
    case PAID     = 'paid';      // Успішно оплачено (підтверджено вебхуком)
    case FAILED   = 'failed';    // Помилка (API впало АБО вебхук приніс відмову)
    case CANCELED = 'canceled';  // Час посилання вийшов або скасовано
}
