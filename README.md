# Laravel Payment Manager

Гнучкий та масштабований пакет для управління платежами у Laravel. Підтримує Charge, Recurring та легко розширюється для будь-яких кастомних інтеграцій.

## Вимоги
* PHP 8.2+
* Laravel 11.0+

## Швидкий старт
```bash
composer require meridaura/payment-manager
php artisan payment-manager:install
php artisan migrate
```

## Документація

1. [налаштування конфігурації](docs/config.md)
2. [налаштування serviceProvider](docs/serviceProvider.md)
3. [створення драйвера та його обробників](docs/makeDriver.md)
4. [обробка вебхука](docs/webhook.md)
5. [приклад використання](docs/example.md)

