# interruptorFactory

Абстрактные фабрики которые позволяют обернуть callback в определенный interruptor.

## HttpAbstractFactory
Фабрика позволяет обернуть callback в интерраптор, 
который выполнить прерывание по средствам отправки callback на сервер для выполнения.

```php
    InterruptAbstractFactoryAbstract::KEY => [
        'cron' => [
            HttpAbstractFactory::KEY_CLASS => Http::class,
            HttpAbstractFactory::KEY_CALLBACK_SERVICE => 'callback_name'
        ],
    ],
```

## ProcessAbstractFactory

## QueueAbstractFactory
