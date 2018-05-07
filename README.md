# rollun-callback

## Callback
 
Это invockable объект -  обертка для **Callable**.   
Умеет сериализовываться, даже если в него "обернуто" замыкание (анонимная функция).

### Как работает Callback:

	$callable = function($val){return 'Hello ' . $val;};
	$callback = new Callback($callable); // $callable - is any type of \Callable
	var_dump($callback('World')); //'Hello World'

### Если результат нужен в виде Promise:
	...
    $promise = new Promise();
	$resultPromise = $promise->then($callback);
	$promise->resolve('World'); //run!
	var_dump($resultPromise->wait()); //'Hello World'

## Interruptor

Реализует интерфейс `InterruptorInterface`.
Так же существует абстрайктный - `InterruptorAbstract` клас который основан на `Callback` 
и реализует интерфейс `InterruptorInterface`.

Разновидность **Callback** для "параллельного" выполнения кода (на другом сайте, в другом процессе, ч-з очередь ...). 
Обычно вызов `$interruptor()` не возвращает результат выполнения **Callable**, зато сразу возвращает управление.   
Например в `Interruptor\Process` стартует новый процесс.  
После вызова  `$info = $interruptor()`, в `$info` будет массив с информацией о процессе (PID, ...).  
Если нужен результат выполнения **Callable**, используйте **Promise**.

> Более детально вохможно ознакомиться [тут 'Callback README'](https://github.com/rollun-com/rollun-callback/blob/master/docs/Callback.md)

При использовании интерапторов для того что бы ваш каллбек имел возможность импользовать сессию, вам необходимо передавать ее вручную.

---
## [Оглавление](https://github.com/rollun-com/rollun-skeleton/blob/master/docs/Contents.md)

---

Каркас для создания приложений. 

* [Стандарты](https://github.com/rollun-com/rollun-skeleton/blob/master/docs/Standarts.md)

* [rollun-callback README](https://github.com/rollun-com/rollun-callback/blob/master/docs/Callback.md)

* [Webhook README](https://github.com/rollun-com/rollun-callback/blob/master/docs/Webhook.md)

* [Queue README](https://github.com/rollun-com/rollun-callback/blob/master/docs/Webhook.md)

* [CallbackFactory README](https://github.com/rollun-com/rollun-callback/blob/master/docs/CallbackFactory.md)

* [InterruptorFactory README](https://github.com/rollun-com/rollun-callback/blob/master/docs/InterruptorFactory.md)

* [Webhook QuickStart](https://github.com/rollun-com/rollun-callback/blob/master/docs/InterruptorFactory.md)

