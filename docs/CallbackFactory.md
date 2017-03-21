# CallbackAbstractFactoryAbstract

Абстрактная фабрика которя являеться родителем фабрик для создания разного рода **Interruptor**

# MultiplexerAbstractFactory

Фабрика для создания Multiplexer. 

Глянем его конфиг:

```php
    AbstractInterruptorAbstractFactory::KEY => [
        'min_multiplexer' => [
            MultiplexerAbstractFactory::KEY_CLASS => Example\CronMinMultiplexer: :class,
            //MultiplexerAbstractFactory::WRAPPED_CLASS => Process:class,
            MultiplexerAbstractFactory::KEY_INTERRUPTERS_SERVICE => [
                'cron_sec_ticker'
            ]
        ],
    ],
```
Давайте разберем структуру конфига
* `'min_multiplexer'` - имя по которому можно будет построить(запросить) данный Multiplexer из ServiceManager.
* `CronMultiplexerFactory::KEY_CLASS` - Класс Callback который будет использован.
> Так как мы рассматриваем Multiplexer, то кданный класс должен быть его наследником.
* `CronMultiplexerFactory::KEY_INTERRUPTERS_SERVICE` - имена сервисов мультиплексоров
* `CronMultiplexerFactory::WRAPPED_CLASS` - Класс интераптора обертки. 

которые будут переданы в multiplexer.
> Данный параметр не обязателен.

# TickerAbstractFactory

Фабрика для создания Ticker interruptor.

Рассмотрим его конфиг:

```php
    AbstractInterruptorAbstractFactory::KEY => [
        'cron' => [
            TickerAbstractFactory::KEY_CLASS => \rollun\callback\Callback\Interruptor\Ticker::class,
            TickerAbstractFactory::KEY_CALLBACK => 'min_multiplexer',
            TickerAbstractFactory::KEY_TICKS_COUNT => 60,
            TickerAbstractFactory::KEY_TICK_DURATION => 1,
            TickerAbstractFactory::KEY_DELAY_MC => 0,
            TickerAbstractFactory::WRAPPED_CLASS => Process:class,
        ]
    ],
```

Давайте разберем структуру конфига
* `'cron'` - имя по которому можно будет построить(запросить) данный Ticker из ServiceManager.
* `TickerAbstractFactory::KEY_CLASS` - Класс Callback который будет использован.
 > Так как мы рассматриваем Ticker, то кданный класс должен быть его наследником.
* `TickerAbstractFactory::KEY_CALLBACK` - Имя сервиса по которому можно получить функцию(callback) которую хотим вызвать внутри Ticker.
* `TickerAbstractFactory::KEY_TICKS_COUNT` - Количество вызовов функции(callback).   
 > Данный параметр не является обязательным. 
* `TickerAbstractFactory::KEY_TICK_DURATION` - Время выделеное под каждый вызовов функции(callback). 
> Задаеться в секундах. Время задержки перед каждыми последующим вызовом. Данный параметр не является обязательным.
* `TickerAbstractFactory::KEY_DELAY_MC` - Задержка перед запуском Ticker. 
* `TickerAbstractFactory::WRAPPED_CLASS` - Класс интераптора обертки. 
> Задаеться в микросекундах. Данный параметр не является обязательным. 
