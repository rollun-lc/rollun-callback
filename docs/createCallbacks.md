## Проблеми при створенні колбека
Якщо при виклику Interrupter у хендлері виникає помилка



**Serialization of 'Closure' is not allowed**

Необхідно додати до файлу колбека:

``` php
public function __sleep(): array
{
    return ['dataStore'];
}

public function __wakeup(): void
{
   InsideConstruct::initWakeup([
       'logger' => LoggerInterface::class,
   ]);
}
```
## Проблеми при роботі з датасторами


Якщо виникає виключення:

**Unable to resolve servise "orders" to a factory; are you certain you provided it during configuration?**

Необхідно додати наступний код до конфігураційного файлу:

```php
TableGatewayAbstractFactory::KEY => [
    'orders' => [],
],
```