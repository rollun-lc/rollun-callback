## QueueDbAdapter

`rollun\callback\Queues\Adapter\DbAdapter` - адаптер, реализующий хранение очереди сообщений в БД.
Для работы нужен Db адаптер `Zend\Db\Adapter\Adapter`. `QueueDbAdapter` реализует интерфейсы 
`ReputationVIP\QueueClient\Adapter\AdapterInterface` и `rollun\callback\Queues\Adapter\DeadMessagesInterface`
Первый представляет интерфейс адаптера, который определяет где и каким образом будут храниться сообщения, 
а второй - интерфейс для работы с мертвыми сообщениями.
Для создания `QueueDbAdapter` существует фабрика `DbAdapterAbstractFactory`.

Адаптеру для работы с клиентом очереди можна указывать параметр `timeInFlight`, который указывает на то сколько времени сообщение
будет недоступно из очереди, если его не удалить.

Также адаптеру можно передать параметр `maxReceiveCount` (`default=0`) который указывает сколько раз можно 
взять сообщение из очереди до того как оно будет считаться мертвым. 
Мертвые сообщения нельзя достать методом `getMessages()`, нельзя посчитать методом `getNumberMessages()`,
метод `isEmpty()` для очереди, где есть только мертвые сообщения, вернет  `true`.

Для работы с мертвыми сообщениями `QueueDbAdapter` реализует методы `getNumberDeadMessages()`, `getDeadMessages()` 
и `deleteDeadMessages()`.

Метод `getNumberDeadMessages()` возвращает количество мертвых сообщений;
Метод `getDeadMessages()` возвращает найденые мертвые сообщения и тут же их удаляет;
Метод `deleteDeadMessages()` удаляет указаное количество мертвых сообщений;

Пример:

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