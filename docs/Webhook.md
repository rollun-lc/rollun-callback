# webhook

**webhook** подвид [interruptor](./Callback.md#Interruptor) который вызываються с помощью http запроса.
Сам **Callback** может быть переданым в запросе, либо вызван как удаленная процедура. 

## Введение

Существуют несколько типов роутов.
Системные и пользовательские, системные - которые используют скрипты(ajax запросы) для работы и получения данных.
И пользовательские - используются пользователями для получения отрендереной html странички.

Системные в большинстве случаев возвращают json ответ либо ответ возвращаться пустым или не имеет особого значения.
В данном примере мы рассмотрим один из системных роутов которые возвращают пустой ответ.

Роут  `/webhook[/{resourceName}]`  используется для получения **interrupt**(**webhook**) вызовов.
Где  interrupt-name  - имя требуемого interruptor.

Используя принцип **action-render middleware** мы разделяем обработку запроса на 2 pipe первый выполняет action,
а второй готовит и возвращает результат. Так как в данном роуте ответ не столь важен, мы подробно рассмотрим только action часть.

## webhook - **action-rendermiddleware**

### Router
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

### ActionRender


Давайте посмотрим теперь найдет его настройки.

```php
ActionRenderAbstractFactory::KEY_AR_SERVICE => [
        'webhookActionRender' => [
            ActionRenderAbstractFactory::KEY_AR_MIDDLEWARE => [
                ActionRenderAbstractFactory::KEY_ACTION_MIDDLEWARE_SERVICE => 'webhookLLPipe',
                ActionRenderAbstractFactory::KEY_RENDER_MIDDLEWARE_SERVICE => JsonRendererAction::class
            ]
        ]
    ],
```
Это стандартный конфиг ActionRender, 
так что давайте пока обратим внимание на
`ActionRenderAbstractFactory::KEY_ACTION_MIDDLEWARE_SERVICE => 'webhookLazyLoad'`, 
и рассмотрим Action часть нашего `webhookActionRender`.

### LazyLoadAbstractFactory

И так вот конфиг `LazyLoadAbstractFactory`. 
```php
LazyLoadPipeAbstractFactory::KEY => [
        'webhookLLPipe' => LazyLoadInterruptMiddlewareGetter::class,
    ],
```
Данная фабрика позволяет получить middleware во время премя запроса, и установить их в pipe. 

По умолчанию используеться 2 типа interruptor middleware 

* HttpInterruptorAction - обрабатывает HttpInterruptor запросы.

* InterruptorCallerAction - middleware который просто запустит переданый ему Interruptor.
> Запустит тот интераптор который вернет SM по имени `resourceName` переданом в урле.
