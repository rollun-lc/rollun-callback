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

> На вопрос `Install cron multiplexer with Examples ? (Yes/No)` нужно ответиь Yes для создание тестовых примеров.

4) Удостовертесь что созданы следующее конфиг файлы.

* `rollun.actionrender.ActionRender.dist.local.php`
* `rollun.actionrender.BasicRender.dist.local.php`
* `rollun.actionrender.LazyLoadPipe.dist.local.php`
* `rollun.actionrender.MiddlewarePipe.dist.local.php`
* `rollun.callback.Cron.dist.local.php`
* `rollun.callback.MiddlewareInterruptor.dist.local.php`
* `rollun.logger.Logger.dist.local.php`
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
> Имя интерапптора - `cron`, потому что, оно берется из url - `localhost:8080\webhook\{interruptor-name}`.

Для обработки данного запроса давайте напишим middleware 
```php
<?php
     use Psr\Http\Message\ServerRequestInterface as Request;
     use Psr\Http\Message\ResponseInterface as Response;
     
     class CronMiddleware implements \Zend\Stratigility\MiddlewareInterface {
         public function __invoke(Request $request, Response $response, callable $out = null) {
            file_put_contents(\rollun\installer\Command::getDataDir() . "cron", (new DateTime())->format("Y-m-d H:i:s") . "|[Cron_min]\n");
         }
     }
```

И Добавить его в конфиг контейнера(ServiceManager).

```php
<?php
     return [
        'dependencies' =>  [
            'invokables' => [
                'cron' => CronMiddleware::class
            ],
        ],
     ];
```

Если бы мы использовали чистый zend, нам бы пришлось добавлять конфиг роута, указываяю туда данный сервис. Но так как мы импользуем библиотеку
**rollun-callback**, она сделать это за нас. Так же она позволяет нам не создавать подобные middleware, а просто описать нужный **callback**
и положить его в конфиг container с тем именем, по которому мы хотим к нему обратиться по http.
Она сама обернет его в middleware который его выполнит.

Давайте заменим написанный нами middleware на анонимную функцию(callback).

```php
<?php
     return [
        'dependencies' =>  [
            'invokables' => [
                'cron' => function ($value) { 
                    file_put_contents(\rollun\installer\Command::getDataDir() . "cron", (new DateTime())->format("Y-m-d H:i:s") . "|[Cron_min]\n");
                },
            ],
        ],
     ];
```

Теперь при каждой нотификации крона, в файл будет записано время когда оно было обработано.

Так, а что есл нам нужно запустит сразу списко функций ?
Тогда мы можем воспользоваться однм из инерапторов **Multiplexer**(мультиплексор), он позволяет нам выполнить целый список фукнций-обработчиков.

Создать мы его можем используя Абстракную фабрику. Более подробно можно [прочесть тут]().

```php
    CallbackAbstractFactoryAbstract::KEY => [
        'cron' => [
            MultiplexerAbstractFactory::KEY_CLASS => Multiplexer::class,
            MultiplexerAbstractFactory::KEY_CALLBACKS_SERVICES => [
                function ($value) { 
                    file_put_contents(\rollun\installer\Command::getDataDir() . "cron1", (new DateTime())->format("Y-m-d H:i:s") . "|[Cron_min]\n");
                },
                function ($value) { 
                    file_put_contents(\rollun\installer\Command::getDataDir() . "cron2", (new DateTime())->format("Y-m-d H:i:s") . "|[Cron_min]\n");
                },
            ]
        ],
    ],
```

Тпереь после каждого обращения в крон, мы будем запускать сразу две функции. 
И у нас будут созданы 2 файла в который будет записанно время когда они были обработаны.

А что если нам нужно отреагировать на один вызов крона, много кратным вызовом наших функций-калбеков.
Допустим нам нужно обрабатывать задачу каждую секунду.  
В этом случае, крон нам не поможет, так как минимально может отправлять только минутные запросы.
И так, для того что бы добавить возможность запуска ежесекундных операции, нам нужно создать секундный тикер.

```php
    CallbackAbstractFactoryAbstract::KEY => [
        'cron' => [
            TickerAbstractFactory::KEY_CLASS => Ticker::class,
            TickerAbstractFactory::KEY_CALLBACK => 'cron_multiplexer',
        ],
    ]
```

Мы переименовали наш пердыдущий multiplexer(мультиплексор) с именем `cron` в `cron_multiplexer`, а интерраптор тикера назвали `cron`.
Это сделано для того что бы при запросе на `localhost:8080/webhook/cron` у нас запускался ticker и он уже в 
свою очередь запустит наш мультиплексор заданное количество раз.

> Как мы помним, имя интерапптора - `cron`, береться из url - `localhost:8080\webhook\{interruptor-name}`.

> Более детально о настройке ticker [можно почитаь тут](./InterruptorFactory.md#TickerAbstractFactory)

Конфиг целиком

```php
    CallbackAbstractFactoryAbstract::KEY => [
        'cron' => [
            TickerAbstractFactory::KEY_CLASS => Ticker::class,
            TickerAbstractFactory::KEY_CALLBACK => 'cron_multiplexer',
        ],
        'cron_multiplexer' => [
            MultiplexerAbstractFactory::KEY_CLASS => Multiplexer::class,
            MultiplexerAbstractFactory::KEY_CALLBACKS_SERVICES => [
                function ($value) { 
                    file_put_contents(\rollun\installer\Command::getDataDir() . "cron1", (new DateTime())->format("Y-m-d H:i:s") . "|[Cron_sec]\n");

                },
                function ($value) { 
                    file_put_contents(\rollun\installer\Command::getDataDir() . "cron2", (new DateTime())->format("Y-m-d H:i:s") . "|[Cron_sec]\n");

                },
            ]
        ],
    ],
```
А теперь давайте попробуем скомбенировать все вместе, и при каждом вызове `cron` мы будем запукать один раз мультиплексор крона, 
и шестдесят раз секундный мультиплексор. 
  
Для этого нам нужно два мультплексера и один тикер для умножения частоты возовов. 

```php
    CallbackAbstractFactoryAbstract::KEY => [
        'cron' => [
            MultiplexerAbstractFactory::KEY_CLASS => Multiplexer::class,
            MultiplexerAbstractFactory::KEY_CALLBACKS_SERVICES => [
                function ($value) { 
                    file_put_contents(\rollun\installer\Command::getDataDir() . "cron1", (new DateTime())->format("Y-m-d H:i:s") . "|[Cron_min]\n");
                },
                function ($value) { 
                    file_put_contents(\rollun\installer\Command::getDataDir() . "cron2", (new DateTime())->format("Y-m-d H:i:s") . "|[Cron_min]\n");
                },
                "sec_ticker",
            ]
        ],
        'sec_ticker' => [
            TickerAbstractFactory::KEY_CLASS => Ticker::class,
            TickerAbstractFactory::KEY_CALLBACK => 'sec_multiplexer',
        ],
        'sec_multiplexer' => [
            MultiplexerAbstractFactory::KEY_CLASS => Multiplexer::class,
            MultiplexerAbstractFactory::KEY_CALLBACKS_SERVICES => [
                function ($value) { 
                    file_put_contents(\rollun\installer\Command::getDataDir() . "cron1", (new DateTime())->format("Y-m-d H:i:s") . "|[Cron_sec]\n");
                },
                function ($value) { 
                    file_put_contents(\rollun\installer\Command::getDataDir() . "cron2", (new DateTime())->format("Y-m-d H:i:s") . "|[Cron_sec]\n");
                }
            ]
        ],
    ]
```

Как мы можем заметить для того что бы добавить новый обработчик, нам достаточно иметь возромжность достать его из контейнера(SM) по имени.
## WebHook - interrupter receiver 

Как мы говорили ранее, **webhook** так же может выполнить пришедший в запрсе сериализованный callback. 
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
    file_put_contents(\rollun\installer\Command::getDataDir() . $value, (new DateTime())->format("Y-m-d H:i:s") . "|[Cron_sec]\n");
}, "http://localhost:8080/webhook/httpCallback");
$httpInterrupt("first");
```
После выполнения, мы должны увидеть файлы с именем переданым в параметр функции - `$value`.
> Переданная функция не очень коретная, так как, создавать файлы на удалееном сервере не правильно, 
но в данном случае это самый простой способ показать ее работоспособность
