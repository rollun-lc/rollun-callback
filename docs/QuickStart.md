# WebHook QuickStart 

## Введение

Приложение имеет 3 роута 

1) `/api` - для обработки ajax запросов.(REST запросы в формате RQL)
2) `/webhook` - для обработки нотификаций - запросы уведомления не требующее содержательного ответа. 
3) `/` - для выдачи пользователю html отображения.

В данном туториале мы рассмотрим **WebHook**
WebHook - роут который преднозначен для обработки разного рода нотофикаций, а так же обработки [interrupt](./Callback.md).

## Настройка окружения.

> Запускать все команды нужно в корневой дериктории проекта, если инного не указано в инструкции.

В данном туториале мы рассмотрим два примера:

1) Получение нотификации от крона([cron](https://en.wikipedia.org/wiki/Cron)) и ее обработка.

2) Обработка прищедшего interrupt запроса.

И так, для начала что бы мы могли запустить наши примеры вы должны в консоли выполнить

1) `composer update` - что бы установить/обновить все зависимости.

2) `composer lib uninstall` - что бы удалить ранее сгенерированые конфиги и предустановки если такие существуют.

3) `composer lib install` - что бы установить и сконфигурировать наше приложение.

Когда установщик предоставит вам выбор компонентов, нужно выбрать три компонента  

* rollun\logger\Installer  
* rollun\callback\Middleware\MiddlewareInterruptorInstaller  
* rollun\callback\CronInstaller  

4) Удостовертесь что созданы следующее конфиг файлы.

* `rollun.actionrender.ActionRender.dist.local.php`
* `rollun.actionrender.BasicRender.dist.local.php`
* `rollun.actionrender.LazyLoadPipe.dist.local.php`
* `rollun.actionrender.MiddlewarePipe.dist.local.php`
* `rollun.callback.Cron.dist.local.php`
* `rollun.callback.MiddlewareInterruptor.dist.local.php`
* `rollun.logger..dist.local.php`
* `rollun.promise.Entity.dist.local.php`
* `rollun.promise.Promise.dist.local.php`


5)

### Запуск php built-in сервера 
Данный пункт можно пропустить если у вас уже запущен web сервер.
Теперь вам нужно запустить приложение на сервере, для этого вам достаточно запустить в консоли.
В дальнейшем по тексту будем считать что используемый сервер висит на 'localhost:8080'

`composer server` 

6) 

### Запуск тестов

Теперь нужно выполнить последний пункт - проверку что все установленно корректно.
Для этого запустите тесты, сделать это можно выполнив команду из консоли.

`composer run_test`

Если тесты прошли успешно - приложение сконфигурировано правильно. В ином случае попробуйте провести всю процедуру заново.

## WebHook - Cron
 
Давайте рассмотрим системную утилиту  `cron`  в качестве отправителя нотификации.
В данном случае он будет оповещать приложение что прошла минута.
Оповещение будет приходить в качестве **http get** запрос на *ulr* `localhost:8080\webhook\cron`.
 
Давайте настроим крон на отправку этой нотификации.
Для этого вам нужно запустить команду `crontab -e`
и добавить такую строку `* * * * * wget localhost:8080/webhook/cron 2&>/dev/null`
 
По своей сути запрос не требует ответа, так как является лишь средством оповещения.
Соответственно наше приложение должно отреагировать на это запустить интераптор с именем `corn`.
> Имя интерапптора - `cron`, потому что оно береться из url - `localhost:8080\webhook\{interruptor-name}`.

И так, давайте посмотрим на интераптор для обработки нотификации от cron.

```php
    AbstractInterruptorAbstractFactory::KEY => [
        'min_multiplexer' => [
            MultiplexerAbstractFactory::KEY_CLASS => Example\CronMinMultiplexer::class,
            MultiplexerAbstractFactory::KEY_INTERRUPTERS_SERVICE => [
                
            ]
        ],
        'cron' => [
            TickerAbstractFactory::KEY_CLASS => \rollun\callback\Callback\Interruptor\Ticker::class,
            TickerAbstractFactory::KEY_WRAPPER_CLASS => \rollun\callback\Callback\Interruptor\Process::class,
            TickerAbstractFactory::KEY_CALLBACK => 'min_multiplexer',
            TickerAbstractFactory::KEY_TICKS_COUNT => 1,
        ]
    ],
```

Мы видим тут два интераптора:
1) мультиплексор - будет запускать все задачи которые нужно выполнять каждую минуту.
2) тикер - интераптор который запустить указанный [callback]() определенное количество раз с заданным интервалом.
Собственно он и запустит наш мультиплексор.

Давайте усложним нашу задачу. Допустим нам нужно обрабатывать задачу каждую секунду.
В этом случае, крон нам не поможет, так как минимально может отправлять только минутные запросы.
И так, для того что бы добавить возможность запуска ежесекундных операции, нам нужно создать секундный тикер.

```php
    AbstractInterruptorAbstractFactory::KEY => [
        'cron_sec_ticker' => [
            TickerAbstractFactory::KEY_CLASS => \rollun\callback\Callback\Interruptor\Ticker::class,
            TickerAbstractFactory::KEY_WRAPPER_CLASS => \rollun\callback\Callback\Interruptor\Process::class,
            TickerAbstractFactory::KEY_CALLBACK => 'sec_multiplexer',
        ],
    ]
```

> Более детально о настройке multiplexer или interruptor [можно почитаь тут](./InterruptorFactory.md)


Теперь нужно создать наш мультиплексор - который будет запускать пул задать на выполнение, и добавить на выполнение в минутный мультиплексор.

```php
    AbstractInterruptorAbstractFactory::KEY => [
       'sec_multiplexer' => [
                   MultiplexerAbstractFactory::KEY_CLASS => Example\CronSecMultiplexer::class,
       ],
       'min_multiplexer' => [
           MultiplexerAbstractFactory::KEY_CLASS => Example\CronMinMultiplexer::class,
           MultiplexerAbstractFactory::KEY_INTERRUPTERS_SERVICE => [
               'cron_sec_ticker'
           ]
       ],
       'cron_sec_ticker' => [
           TickerAbstractFactory::KEY_CLASS => \rollun\callback\Callback\Interruptor\Ticker::class,
           TickerAbstractFactory::KEY_WRAPPER_CLASS => \rollun\callback\Callback\Interruptor\Process::class,
           TickerAbstractFactory::KEY_CALLBACK => 'sec_multiplexer',
       ],
       'cron' => [
           TickerAbstractFactory::KEY_CLASS => \rollun\callback\Callback\Interruptor\Ticker::class,
           TickerAbstractFactory::KEY_WRAPPER_CLASS => \rollun\callback\Callback\Interruptor\Process::class,
           TickerAbstractFactory::KEY_CALLBACK => 'min_multiplexer',
           TickerAbstractFactory::KEY_TICKS_COUNT => 1,
       ]
    ]
```

## WebHook - interrupter receiver 

Как мы говорили ранее, **webhook** так же может принимать interruptor и выполнять их. 
> Более детально оп принципе работы можно [прочесть тут](./Webhook.md)

Для того что бы выполнить запрос на удаленном ресурсе, можно использовать [**Http** Interruptor](./Callback.md#Http).
> для обработки запросов, их нужно отправлять на url `localhost:8080/webhook/httpCallback`.

Давайте отправим какой то interrupt на сервер.
Для этого создайте php скрипт со следующим сождержанием

```php
<?php
require 'vendor/autoload.php';
require_once 'config/env_configurator.php';
$container = require 'config/container.php';
\rollun\dic\InsideConstruct::setContainer($container);

$httpInterrupt = new \rollun\callback\Callback\Interruptor\Http(function($value) {
      $file = fopen(\rollun\installer\Command::getDataDir() . $value, "w+");
      fwrite($file, "$value");
}, "http://localhost:8080/webhook/httpCallback");
$httpInterrupt("first");
```
После выполнения, мы должны увидеть файлы с именем переданым в параметр функции - `$value`.
> Переданная функция не очень коретная, так как, создавать файлы на удалееном сервере не правильно, 
но в данном случае это самый простой способ показать ее работоспособность

