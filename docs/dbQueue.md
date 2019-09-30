## QueueDbAdapter

`rollun\callback\Queues\Adapter\DbAdapter` - адаптер, реализующий хранение очереди сообщений в БД.
Для работы нужен Db адаптер `Zend\Db\Adapter\Adapter`. `QueueDbAdapter` реализует интерфейсы 
`ReputationVIP\QueueClient\Adapter\AdapterInterface` и `rollun\callback\Queues\Adapter\DeadMessagesInterface`
Первый представляет интерфейс адаптера, который определяет где и каким образом будут храниться сообщения, 
а второй - интерфейс для работы с мертвыми сообщениями.
Для создания `QueueDbAdapter` существует фабрика `DbAdapterAbstractFactory`.

Адаптеру для работы с клиентом очереди можна указывать параметр `timeInFlight`, который указывает на то, 
сколько времени сообщение будет недоступно из очереди, если его не удалить.

Также адаптеру можно передать параметр `maxReceiveCount` (`default=0`) который указывает сколько раз можно 
взять сообщение из очереди до того как оно будет считаться мертвым. 
Мертвые сообщения нельзя достать методом `getMessages()`, нельзя посчитать методом `getNumberMessages()`,
метод `isEmpty()` для очереди, где есть только мертвые сообщения, вернет  `true`.

### Реализация интерфейса `ReputationVIP\QueueClient\Adapter\AdapterInterface`:

`QueueDbAdapter` хранит каждую очередь в отдельной таблице. Имя таблицы состоит из:
* префикса `queue_`;
* имени очереди;
* суффикса, в который записываются параметры очереди - `_{timeInFlight}_{maxReceiveCount}`

Методы:

* `listQueues($prefix = '')` - возвращает список очередей;
* `createQueue($queueName)` - создает таблицу (если ее нет);
* `deleteQueue($queueName)` - удаляет таблицу;
* `renameQueue($sourceQueueName, $targetQueueName)` - переименовывает таблицу;
* `purgeQueue($queueName, Priority $priority = null)` - удаляет записи в таблице (включая мертвые)
* `getNumberMessages($queueName, Priority $priority = null)` - возвращает количество сообщений доступпніх к прочтению
(не учитываются мертвые сообщения и сообщения в режиме `in flight`);
* `addMessage($queueName, $message, Priority $priority = null, $delaySeconds = 0)` - добавляет сообщение в очередь, 
параметр `$message` может иметь любой тип;
* `getMessages($queueName, $nbMsg = 1, Priority $priority = null)` - возвращает указаное количество доступных к
прочтению сообщений. Возвращает массив сообщений. Сообщение в данном случае являет собой массив вида:
```php
$message = [
'id' => '', //int
'time-in-flight' => time(), //int
'delayed-until' => time(), //int
'Body' => '', // mixed
'priority' => '', //int
];
```
* `deleteMessage($queueName, $message)` - удаляет сообщение из очереди. Параметр `$message` должен иметь тип - 
```php
$message = [
'id' => '', //int
'time-in-flight' => time(), //int
'delayed-until' => time(), //int
'Body' => '', // mixed
'priority' => '', //int
];
```
* `isEmpty($queueName, Priority $priority = null)` - проверяет есть ли в очереди сообщения 
(учитываются только НЕ метрвые сообщения)  

### Реализация интерфейса `rollun\callback\Queues\Adapter\DeadMessagesInterface`:

Для работы с мертвыми сообщениями `QueueDbAdapter` реализует методы `getNumberDeadMessages()`, `getDeadMessages()` 
и `deleteDeadMessages()`.

Метод `getNumberDeadMessages()` возвращает количество мертвых сообщений;
Метод `getDeadMessages()` возвращает найденые мертвые сообщения и тут же их удаляет;
Метод `deleteDeadMessages()` удаляет указаное количество мертвых сообщений;

### Пример работы адаптера:

```php

$timeInFlight = 0;
$maxReceiveCount = 1; //сообщения будут считаться мертвыми после первого считывания
$object = new rollun\callback\Queues\Adapter\DbAdapter($container->get('db'), $timeInFlight, $maxReceiveCount);
$object->createQueue('a');
$object->addMessage('a', 'message1');
$object->addMessage('a', 'message2');
$object->addMessage('a', 'message3');
$object->addMessage('a', 'message');
$object->getMessages('a', 3); // три из четырех сообщений теперь "мертвые"
sleep(1);
echo $object->isEmpty('a'); //false - в очереди еще есть немертвые сообщения

echo $object->getNumberDeadMessages('a'); // 3
//мертвые сообщения которые содержатся в переменной $deadMessages теперь удалены из БД
$deadMessages = $object->getDeadMessages('a', 10); 
echo $object->getDeadMessages('a'); // array()
    
```