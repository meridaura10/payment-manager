<?php

namespace Meridaura\PaymentManager\Enums;

enum PaymentStageEnum
{
    case CREATED;  // Тільки-но створено в базі (ще без лінка)
    case PENDING;   // Отримали лінк на оплату, чекаємо дій клієнта
    case PAID;     // Успішно оплачено (підтверджено вебхуком)
    case FAILED;    // Помилка (API впало АБО вебхук приніс відмову)
    case CANCELED; // скасовано
}
