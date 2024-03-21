# rollun-callback

`rollun-callback` - библиотека, которая кроме полезных `callable` объектов дает возможность передавать код на 
выполнение другому процессу, машине или очереди. Так же эта библиотека дает возможность быстро поднять `endpoint` для 
работы `webhook` - сервис, который сразу же возвращает управление, несмотря на длительность возможных операций.


## Установка

1. Установите с помощьою [composer](https://getcomposer.org/)
```bash
composer require rollun-com/rollun-callback
```
2. Подключите `rollun\callback\ConfigProvider` в ваш конфигурационный файл.
3. Подключите роутинг в ваш конфигурационный файл роутингов (это обычно `config/routes.php`).

Для запуска тестов нужно запустить `php-fpm` сервер:
```bash
php -S localhost:8000 -t public
```
> Переменная окружения HOST должна совпадать с хостом на котором запускаеться php-fpm, в данном случае это 
http://localhost:8000


## Callback и Interrupter

`Callback` и `Interrupter` - это `callable`(invokable) объекты. Главное отличие между ними, это то что `Interrupter` 
вычисляются параллельно и сразу же передают управление. `Callback` - это invokable объекты, которые вносят с помощью 
композиции дополнительные возможности.
### Service as callback

Чтобы добавить сервис как вебхук достаточно добавить alias

```php
    SerializedCallbackAbstractFactory::class => [
        'Callback' => Callback::class,
    ],
    CallablePluginManagerFactory::KEY_INTERRUPTERS => [
        'dependencies' => [
            'aliases' => [
                'Callback' => Callback::class,
            ],
        ],
    ],

```

#### SerializedCallback
 
`SerializedCallback` - это `Callback`, обертка для `callable`.   
Умеет сериализовываться, даже если в него "обернуто" замыкание (анонимная функция). Почти все последующие объекты, 
которые будут описаны далее используют этот объект для того что бы `callable` был сериализуемый.

Пример:

```php
$callable = function($val) {return 'Hello ' . $val;};
$callback = new SerializedCallback($callable); // $callable - is any type of \Closure
var_dump($callback('World')); // 'Hello World'
```


#### Multiplexer

`Multiplexer` - это `Callback`, который принимает массив из `callable`.
Так же может быть сериализован, так как заворачивает все callable в `SerializedCallback`.
При вызове `Multiplexer` вызывает все `callable` по приоритетам, указанные в качестве ключей массива.
Возвращает значение в зависимости от типа `callable` (`Interrupter` или `Callback`).

Пример:

```php
$multiplexer = new Multiplexer([
    1 => function ($val) { echo "1. $val; "; },
    3 => function ($val) { echo "3. $val; "; },
    2 => function ($val) { echo "2. $val; "; },
]);
$multiplexer('The same'); // 1. The same; 2. The same; 3. The same;
```

Иногда в логах полезно видеть имена мультиплексера и калбеков. Имя мультиплексера можно передать как третий необязательный
аргумент конструктора (если его не передать, то имя присвоится автоматически как 'undefined'). А для того чтобы можно 
было присвоить имя калбекам создан объект rollun\callback\Callback\Multiplexer\CallbackObject. Вы можете передать его 
вместо калбека, или массив (который сам преобразуется в этот объект).  

```php
$multiplexer = new Multiplexer([
    1 => CallbackObject(function ($val) { echo "1. $val; "; }, 'callbackName1'),
    3 => [
            CallbackObject::CALLBACK_KEY => function ($val) { echo "3. $val; "; },
            CallbackObject::NAME_KEY => 'callbackName3'
         ],
    2 => function ($val) { echo "2. $val; "; },
], 'multiplexerName');
$multiplexer('The same'); // 1. The same; 2. The same; 3. The same;
```

#### Ticker 

`Ticker` - это `Callback`, который вызывает переданий ему `callable` заданное количество раз, с заданим 
интервалом и с заданим отложеним вызовом.
Возвращает значение в зависимости от типа `callable` (`Interrupter` или `Callback`).

Пример:

```php
$ticker = new Ticker(function () {
    echo 'I will tick 4 times every 30 seconds after 50 seconds delay';
}, 4, 30, 50000);

$ticker();
```


#### Worker

`Worker` - это `Callback`, который вызивает переданный ему `callable`, значения для которых `Worker` берет с 
очереди.

Пример:

```php
$queue = new Queue('testQueue');
$queue->addMessage('test1');
$queue->addMessage('test2');
$worker = new Worker($queue, function ($value) {
    echo "It is $value; ";
});
$worker(); // It is test1; It is test2;
```

#### Health checker
Абстрактный `Callback` со встроенным расписанием. Валидирует сервисы и в случае невалидности пишет лог.

Пример конфига подключения:
```php
use rollun\callback\Callback\Factory\CallbackAbstractFactoryAbstract;
use rollun\callback\Callback\Factory\HealthCheckerAbstractFactory;
use rollun\callback\Callback\Factory\MultiplexerAbstractFactory;
use rollun\callback\Callback\Multiplexer;
use rollun\callback\Callback\HealthChecker\HealthChecker;
use rollun\callback\Callback\HealthChecker\Validator\PingValidator;

return [
 CallbackAbstractFactoryAbstract::KEY => [
        'min_multiplexer'   => [
            MultiplexerAbstractFactory::KEY_CLASS              => Multiplexer::class,
            MultiplexerAbstractFactory::KEY_CALLBACKS_SERVICES => [
                'pingHealthChecker',
            ],
        ],
        'pingHealthChecker' => [
            HealthCheckerAbstractFactory::KEY_CLASS           => HealthChecker::class,
            HealthCheckerAbstractFactory::KEY_CRON_EXPRESSION => '* * * * *',
            HealthCheckerAbstractFactory::KEY_LOG_LEVEL       => 'error',
            HealthCheckerAbstractFactory::KEY_VALIDATOR       => [
                HealthCheckerAbstractFactory::KEY_CLASS => PingValidator::class,
                PingValidator::KEY_HOST                 => 'http://rollun.local'
            ],
        ]
    ],
];
```

#### Http

`Http` - это `Callback`, который вызывает [webhook](#webhook). Возвращает значение в зависимости от типа `callable` 
(`Interrupter` или `Callback`).

Пример:

```php
$url = 'http://exampe.com/api/webhook/external-callable-service';
$object = new Http($url);
$payload = $object();
```


#### Job 

`Job` - это объект, который может сериализовать и десиарилизовать себе вместе с `callable` и значением для `callable`. 
Таким образом можно сериализовать некоторый `callable` и передать его на выполнение в другую среду, где он 
благополучно десиарилизуеться и вызовет `callable` со значением.


Пример:

```php
$job = new Job(function ($value) {
    echo "Hello $value";
}, 'Word!');

$hash = $job->serializeBase64();

$job = Job::unserializeBase64($hash);
$callback = $job->getCallback();
$value = $job->getValue();

$callback($value);
```

#### Interrupter

Interrupter разновидность `callable` для "параллельного" выполнения кода (на другом сайте, в другом процессе, через 
очередь и тд). Реализует интерфейс `InterruptorInterface`.
Так же существует абстрактный - `InterruptorAbstract` клас который все принимающие `callable` заворачивает в 
`SerializedCallback` для того чтобы `Interrupter` можно было так же сериализовать и передать на выполнение другому 
интераптору и так до бесконечности.
 
Вызов `$interruptor()` возвращает результат выполнения в виде об'екта `PayloadInterface`.   
Например в `Interruptor\Process` стартует новый процесс.  
После вызова  `$info = $interruptor()`, в `$info` будет массив с информацией о процессе (PID, ...).  
Если нужен результат выполнения `callable`, используйте `Promise` из
[rollun-com/rollun-promise](https://github.com/rollun-com/rollun-promise).

**Виды `Interrupter`:**

* `Process` - позволяет выполнить `callback` в отдельном процессе. Так же, если передать процессу ожидаемое время выполнения
скрипта и `PidKillerInterface`, то через указаное время процесс будет убит.
* `QueueFiller` - при вызове добавляет сериализованное значение в очередь
* `ProcessByName` - позволяет выполнить `callback` по имени в отдельном процессе.
В таком случае выполняемый `callback` не будет создаваться заранее, а так же не будет сериализации. 
Принимает имя вызываемого `callback` в качетсве параметра. Так же, если передать процессу ожидаемое время выполнения
скрипта и `PidKillerInterface`, то через указаное время процесс будет убит.

#### Примеры конфигураций для абстрактных фабрик `Callback` и `Interrupter`.

```php
return [
    SerializedCallbackAbstractFactory::class => [
        //testCallback1 в данному випадку є іменем колбека
        'testCallback1' => 'CallMe::class',
        'testCallback2' => ['service', 'invokableMethod'],
        'testCallback3' => 'CallMe::invokableMethod',
        'testCallback4' => [new TextObject(), 'invokableMethod'],
        'testCallback5' => 'declaredFunction',
        'testCallback6' => function ($value) {
            return $value;
        },
    ],
    ExtractorAbstractFactory::class => [
        'extractor' => [
            ExtractorAbstractFactory::KEY_QUEUE_SERVICE_NAME => 'queueServiceName',
        ],
    ],
    
    CallbackAbstractFactoryAbstract::KEY => [
        // 'multiplexer' - имя мультиплексера
        'multiplexer' => [
            MultiplexerAbstractFactory::KEY_CLASS => Multiplexer::class,
            MultiplexerAbstractFactory::KEY_CALLBACKS_SERVICES => [
                'serializedCallback', // будет именем калбека
                'processInterrupter',
                'ticker',
                'extractor',

                // Пример того как можна паралельно вызвать несколько interrupter
                'processInterrupter',
                'queueInterrupter',
                'httpInterrupter',
            ]
        ],
        'ticker' => [
            TickerAbstractFactory::KEY_CLASS => Multiplexer::class,
            TickerAbstractFactory::KEY_DELAY_MC => 60,
            TickerAbstractFactory::KEY_TICK_DURATION => 30,
            TickerAbstractFactory::KEY_TICKS_COUNT => 4,
        ],
    ],
    InterruptAbstractFactoryAbstract::KEY => [
        'processInterrupter' => [
            ProcessAbstractFactory::KEY_CLASS => Process::class,
            ProcessAbstractFactory::KEY_CALLBACK_SERVICE => 'testCallback',
        ],
        'queueInterrupter' => [
            QueueMessageFillerAbstractFactory::KEY_CLASS => QueueFiller::class,
            QueueMessageFillerAbstractFactory::KEY_QUEUE_SERVICE => 'queueServiceName',
        ],
        'httpInterrupter' => [
            HttpAbstractFactory::KEY_CLASS => Http::class,
            HttpAbstractFactory::KEY_OPTIONS => [
                // options for http client
            ],
            HttpAbstractFactory::KEY_URL => 'http://example.com/api/webhook'
        ],
    ],
];

```


## Webhook

`Webhook` - это сервис, который в зависимости от ресурса будет обрабатывать или `Callback` или `Interupter`.
По сути это `callable`, который который поднимается по названию сервиса, вызывается и отдает ответ.
В зависимости от того будет ли это `Interrupter` или `Callback` буде возвращен результат `PayloadInterface` или 
`mixed` (тип зависит от возвращаемого типа `Callback`) соответственно.


Для того чтобы использовать `webhook`, нужно подключить следующий роутинг, где `resourceName` - это газвание сервиса.

```php
$app->route(
    '/api/webhook[/{resourceName}]',
    WebhookMiddleware::class,
    Route::HTTP_METHOD_ANY,
    'webhook'
);
```

Опісля всі типи колбеків можуть бути викликані вебхуком без додаткової конфігурації.

## Queue

`QueueClient` - очередь, в общем понимании этого термина, реализующая `QueueInterface`.
Для очереди нужен адаптер. Адаптер определяет где и каким образом будут храниться сообщения.
Фабрики `FileAdapterAbstractFactory` и `SqsAdapterAbstractFactory` могут создать файловый адаптер и адаптер для 
очередей Amazon соответственно.

`Message` - единица сообщения в очереди. `Message` можно создать с помощью статического метода-фабрики. У этого 
объекта есть 3 основных метода:

* `getData()` - использовать если адаптер очереди возвращает сообщение в виде массива с обязательными ключами: `id`, 
`Body`. Тогда можно использовать этот метод, для получения оригинального сообщение (пример ниже).
* `getId()` - по аналогии с предыдущим метод, вызвав этот метод будет возвращен `id` из массива, который передаст 
адаптер.
* `getMessage()` - возвращает все сообщение от адаптера целиком.


Адаптерам для работы с клиентом очереди можна указывать параметр `time-in-flight`, который указывает на то сколько времени сообщение
будет недоступно из очереди, если его не удалить.

`SqsAdapter` умеет раюотать с очерлью мертвых сообщений

Пример:

```php
$object = QueueClient(new MemoryAdapter(), 'testAdapter');

$object->addMessage(Message::createInstance('a'));
$object->addMessage(Message::createInstance('b'));
$object->addMessage(Message::createInstance('c'));
$object->addMessage(Message::createInstance('d'));

echo $object->isEmpty() == false; // 1

echo $object->getMessage()->getData() == 'a'; // 1
echo $object->getMessage()->getData() == 'b'; // 1
echo $object->getMessage()->getData() == 'c'; // 1
echo $object->getMessage()->getData() == 'd'; // 1

echo $object->isEmpty() == true; // 1

$object->addMessage(Message::createInstance('a'));
$object->addMessage(Message::createInstance('b'));

$object->purgeQueue();
echo $object->isEmpty() == true; // 1
```

## Pid killer

Представте что у вас есть воркер, который берет c очереди сообщения и запускает новый php процесс (асинхронный),
передавая в качестве параметра только что полученое сообщение. Для того чтобы безопасно реализовать такую идею
и не перезагрузить систему зомби процессами нужен механизм, который позволяет через некоторое время
в любом случае убивать этот процесс. Для этого все и придумана система, которая называется `Pid killer`.

`Pid killer` состоит из трех объектов.
- `PidKiller\WorkerManager` - поднимает и контролирует одновременно заданое количество процессов, использует таблицу
`slots` для управления ([sql](/src/Callback/bootstrap.sql))
- `PidKiller\Worker` - это воркер который запускает `callalbe` и прокидывает сообщение прочитанное с очереди в виде аргумента.
Если в конструктуоре `PidKiller\Worker` указан `WriterInterface`, то результат `callable` будет записан в этот `writer`
- `PidKiller\QueueClient` - клиент очереди который изымает из сообщения данные о том через какое время сообщение можно будет изъять из очереди
(это и есть то время которое процесс может жить).
- `PidKiller\LinuxPidKiller` - объект который добавляет в очередь сообщение о pid процессе, который нужно будет убить и
достает из очереди сообщения о процессе который нужно убить и убивает его.


```php
$fileQueueAdapter = new FileAdapter('/tmp/test');
$pidQueue = new PidKiller\ClientQueue($fileQueueAdapter, 'pidqueue');
$workQueue = new PidKiller\ClientQueue($fileQueueAdapter, 'workqueue');

$pidKiller = new PidKiller\PidKiller($pidQueue);
$process = new Process(function () {
   sleep(1000);
});

$worker = new PidKiller\Worker($workQueue, $pidKiller, $process, 10);

// run new process
$worker();

// nothing heppen because it is to early
$pidKiller();

sleep(10);

// kill process
$pidKiller();
```

## Common issues and examples
#### Якщо callback використовує сервіси, то його створення потрібно робити через фабрику, котра передасть сервіси в конструктор. Також можливе використання InsideConstruct (без фабрик)
Приклад конфігурації:
```php
return [
    'dependencies' => [
            'factories' => [
                SomeCallback::class => SomeCallbackFactory::class
            ],
        ],
        SerializedCallbackAbstractFactory::class => [
            'doSomethingCallback' => [SomeCallback::class, 'doSomething'],
        ],
    ]
];
```
### Важливо!!!
Callback неможливо (не варто) серіалізувати разом з сервісами (може виникнути помилка). Колбеки з сервісами повинні використовувати `__sleep` та `__wakeup`
Приклад коллбеку з сервісом:
```php
<?php
namespace App\Callbacks;

use rollun\datastore\DataStore\DbTable;
use rollun\dic\InsideConstruct;

class DBInteractor {
    //$dataStore зберігає сервіс. Його серіалізувати неможна
    private DbTable $dataStore;
    private $number = 0;

    function __construct(DbTable $dataStore, $number = 0) 
    {
        $this->dataStore = $dataStore;
        $this->number = $number;
    }

    //Ця функція буде виконана при серіалізації. Вона повинна повернути імена властивостей дозволених до серіалізації
    function __sleep()
    {
        return ['number'];
    }

    //Звісно післа десеріалізації $dataStore буде пустим і наш коллбек буде недієздатним без нього тож ми використовуємо __wakeup щоб отримати сервіс
    function __wakeup()
    {
        //Швидкий спосіб. Увага! dataStore повинен бути в контейнері, інакше нічого не вийде
        InsideConstruct::initWakeup(['dataStore' => 'dataStore']);
    }

    function doSomething()
    {
        //TODO
    }
}
```
