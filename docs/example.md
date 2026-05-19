# Приклад використання

```php
$data = new ChargePurchaseRequest(
    \App\Models\Order::query()->first(),
    'USD',
    100,
    route('payment.notify', ['gateway' => 'monobank']),
    route('payment.redirect', ['gateway' => 'monobank']),
    [
        'key' => 10,
    ],
    [
        'domain_id' => \Illuminate\Support\Str::uuid(),
    ]
);

$response = \Meridaura\PaymentManager\Facades\PaymentManager::charge($gateway)->purchase($data);

Буде повернено спеціалізований dto клас який може містити в собі типізовану помилку для зручного логування та виведення

Приклад кастомного обробника заданого в драйвері
$response = \Meridaura\PaymentManager\Facades\PaymentManager::subscription($gateway)->setup($data);

Приклад виконристання для вебхука
$response = \Meridaura\PaymentManager\Facades\PaymentManager::webhook($gateway)->handle($request->all());
```