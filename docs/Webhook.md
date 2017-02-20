Существуют несколько типов роутов.
Системные и пользовательские, системные - которые используют скрипты(ajax запросы) для работы и получения данных.
И пользовательские - используются пользователями для получения отрендереной html странички.

Системные в большинстве случаев возвращают json ответ либо ответ возвращаться пустым или не имеет особого значения.
В данном примере мы рассмотрим один из системных роутов которые возвращают пустой ответ.

Роут  `/webhook[/{resourceName}]`  используется для получения interrupt вызовов.
Где  interrupt-name  - имя требуемого interruptor.

Используя принцип **action-render middleware** мы разделяем обработку запроса на 2 pipe первый выполняет action,
а второй готовит и возвращает результат. Так как в данном роуте ответ не столь важен, мы подробно рассмотрим только action часть.
Итак, давайте взглянем на конфиг роута

```php
 'routes' => [
        [
            'name' => 'webhook',
            'path' => '/webhook[/{resourceName}]',
            'middleware' => 'webhookActionRender',
            'allowed_methods' => ['GET', 'POST'],
        ],
    ],    
```

Мы можем видеть что тут используется `webhookActionRender` middleware. 
Давайте посмотрим теперь найдет его настройки.

```php
ActionRenderAbstractFactory::KEY_AR_SERVICE => [
        'webhookActionRender' => [
            ActionRenderAbstractFactory::KEY_AR_MIDDLEWARE => [
                ActionRenderAbstractFactory::KEY_ACTION_MIDDLEWARE_SERVICE => 'webhookLazyLoad',
                ActionRenderAbstractFactory::KEY_RENDER_MIDDLEWARE_SERVICE => 'webhookJsonRender'
            ]
        ]
    ],
```
Это стандартный конфиг ActionRender, 
так что давайте пока обратим внимание на
`ActionRenderAbstractFactory::KEY_ACTION_MIDDLEWARE_SERVICE => 'webhookLazyLoad'`, 
и рассмотрим Action часть нашего `webhookActionRender`.

И так вот конфиг `LazyLoadAbstractFactory`. 
```php
LazyLoadAbstractFactory::KEY_LAZY_LOAD => [
        'webhookLazyLoad' => [
            LazyLoadAbstractFactory::KEY_DIRECT_FACTORY =>
                \rollun\callback\Middleware\Factory\InterruptorDirectFactory::class
        ]
    ],
```
Данная фабрика позволяет нам создавать Middleware на основе переданной ей  directFactory ,
в которую она передает `resourceName` в качестве запрашиваемого сервиса.
Как мы можем увидеть middleware создается с помощью `InterruptorDirectFactory` .
Давайте рассмотрим ее по подробнее:

```php
public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $resourceName = $requestedName;
        if (!$container->has($resourceName)) {
            throw new DirectFactoryException(
                'Can\'t make Middleware\InterruptorAbstract for resource: ' . $resourceName
            );
        }
        $interruptMiddleware = null;
        $resource = $container->get($resourceName);
        switch (true) {
            case is_a($resource, InterruptorInterface::class, true):
                $interruptMiddleware = new InterruptorCallerAction($resource);
                break;
            case is_a($resource, InterruptorAbstract::class, true):
                $interruptMiddleware = $resource;
                break;
            default:
                if (!isset($interruptMiddleware)) {
                    throw new DirectFactoryException(
                        'Can\'t make Middleware\InterruptorAbstract'
                        . ' for resource: ' . $resourceName
                    );
                }
        }
        return $interruptMiddleware;
    }
```

Как мы можем увидеть данная фабрика проверяет наличие данного сервиса в контейнере, а так же проверяте его тип.
Вслучае если это Middleware то гда он его вернет, если это interruptor он обернет его в middleware и вернет.

По умелчанию используеться 2 типа interruptor middleware 

* HttpInterruptorAction - обрабатывает HttpInterruptor запросы.

* InterruptorCallerAction - middleware который просто запустит переданый ему Interruptor.
> Запустит тот интераптор который вернет SM по имени `resourceName` переданом в урле.

##Interruptor 

Один из Interruptor которые работают по умолчанию это - **Cron multiplexer**.
Глянем его конфиг:

```php
AbstractMultiplexerFactory::KEY_MULTIPLEXER => [
        CronMultiplexerFactory::KEY_CRON => [
            CronMultiplexerFactory::KEY_CLASS => Example\CronMinMultiplexer::class,//not require
            CronMultiplexerFactory::KEY_SECOND_MULTIPLEXER_SERVICE => 'cronSecMultiplexer', //not require
            //CronMultiplexerFactory::KEY_INTERRUPTERS_SERVICE => [] not require
        ]
    ],
```
Давайте разберем структуру конфига
* CronMultiplexerFactory::KEY_CLASS - Класс мультиплексера который будет использован.
> Данный параметр не обязателен, по умолчанию будет миспользован стандартный клаас Multiplexer.  
* CronMultiplexerFactory::KEY_SECOND_MULTIPLEXER_SERVICE - имя сервиса секундного мультиплексера.
> Мультиплексер который будет запущен каждую секунду, в течении минуты. Данный параметр не обязателен
* CronMultiplexerFactory::KEY_INTERRUPTERS_SERVICE - имена сервисов мультиплексеров
которые будут переданы в основной multiplexer.
> Данный параметр не обязателен.