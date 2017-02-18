#CronReceiver

**Middleware** которы получает запросы от крона, создает и запускает **CronManager**.

**CronReceiver** - Получает в качетсве зависимости два **Multiplexer**.
Первый - секундный, а второй минутный. Соответсввенно при кажом вызове **CronReceiver**, 
будет срабатывать один раз минутный **Multiplexer** и 60 раз секудный.

**Multiplexer** будут переданы ему фабрикой - **CronReceiverFactory**, а задать их можно в конфиге.
Пример

```php
    'cron' => [
        CronReceiver::KEY_MIN_MULTIPLEXER => 'exampleMinMultiplexor',
        CronReceiver::KEY_SEC_MULTIPLEXER => 'exampleSecMultiplexor',
    ],
```
> По умолчанию используються **exampleMultiplexor**.