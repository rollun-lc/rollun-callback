#Callback , Interruptor и Promiser   

----------

## Callback
 
Это invockable объект -  обертка для **Callable**.   
Умеет сериализовываться, даже если в него "обернуто" замыкание (анонимная функция).

###Как работает Callback:
	$callable = function($val){return 'Hello ' . $val;};
	$callback = new Callback($callable); // $callable - is any type of \Callable
	var_dump($callback('World')); //'Hello World'

###Если результат нужен в виде Promise:
	...
    $promise = new Promise();
	$resultPromise = $promise->then($callback);
	$promise->resolve('World'); //run!
	var_dump($resultPromise->wait()); //'Hello World'


##Interruptor

Реализует интерфейс `InterruptorInterface`.
Так же существует абстрайктный - `InterruptorAbstract` клас который основан на `Callback` 
и реализует интерфейс `InterruptorInterface`.

Разновидность **Callback** для "параллельного" выполнения кода (на другом сайте, в другом процессе, ч-з очередь ...). 
Обычно вызов `$interruptor()` не возвращает результат выполнения **Callable**, зато сразу возвращает управление.   
Например в `Interruptor\Process` стартует новый процесс.  
После вызова  `$info = $interruptor()`, в `$info` будет массив с информацией о процессе (PID, ...).  
Если нужен результат выполнения **Callable**, используйте **Promise**.

Все `Interruptor` должны вернуть 
* Имя машины на которой были запущены (Переменную окружения `MACHINE_NAME`)
* Тип `Interruptor` (имя класса)
* Поле `data` - не обязательное поле сожержащее вывод вложеных **Interruptor**

### Виды Interruptor

* `Http` - Позвояет выполнить **Callback** на удаленной машине.
> Вторым обязательным параметром принимает url куда должен быть отправлен **Callback**.
* `Process` - Позвояет выполнить **Callback** в отдельном процессе
* `Multiplexer` - Позвояет выполнить переданный список других **Interruptor** или **Promise**.
> Принимает на вход массив содержащий **Interruptor** или **Promise** в ином случае будет выброшено исключение.
* `Queue` - Позволяет положить **Callback** в очередь.
* `Extractor` - Позволяет запусть **Callback** из очереди. 

## Promiser

Реализует интерфейс `PromiserInterface`.

###Если результат нужен в виде Promise:
	$callable = function($val){return 'Hello ' . $val;};
    $promiser = new Promiser($callable);
	$resultPromise = $promiser('World');
	var_dump($resultPromise->wait()); //'Hello World'

## Http 

Позволяет отправить **Сallback** выполнятся удаленно - по http.
На вход принимает сам **Сallback** и **url** куда будет отправлен запрос.
  
Так же имеется Middleware pipeLine который обрабатывает запрсы.
В случае если вы хотите обрабатывать (принимать) от **Http** Interruptor вы должны повесить 
на обработку инстанс **HttpReceiver** полученый из фабрики **HttpReceiverFactory**.

Пример:
```php
    $HttpReceiverFactory = new HttpReceiverFactory();
    $http = $HttpReceiverFactory($container, '');
    $app->pipe('/api/http', $http);
```

> Для тестирования **Http** Interruptor вам нужно установить данный PipeLine, а так же переопределить в конфиге `service`
 поле `url` настройки `httpInterruptor`.
 
 
## Multiplexer

Подвид **Interruptor** который позволяет вызывать массивы других **Interruptor** или **Promiser**.
На вход принимает массив который содержит перечень **Interruptor** или **Promiser**.

В случае ошибки одноги из членов массива продолжит свое выполение.
Возвращает массив со списком возвращенных значений **Interruptor** или **Promiser**.

## Extractor

Подвид **Interruptor** который позволяет достать и запустить Job который лежит в очереди.

Метод `extract` позволяет получить ответ из вызванного **Callback**.
Метод `__invoke` возвращает массив с вспомагательными данными.


## Queue

**Interruptor** который позволяет положить **Callback** в очередь.

## Ticker 

**Interruptor** который вызовет переданый **Callback** заданое количество раз, с заданым интервалом.

