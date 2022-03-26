# rollun-callback

`rollun-callback` - библиотека, которая кроме полезных `callable` объектов дает возможность передавать код на 
выполнение другому процессу, машине или очереди. Так же эта библиотека дает возможность быстро поднять `endpoint` для 
работы `webhook` - сервис, который сразу же возвращает управление, несмотря на длительность возможных операций.

* [Документация](https://rollun-com.github.io/rollun-datastore)

## Переход на версию php 8
При переходе на PHP v.8 и Laminas выявлены следующие проблемы.
### 1. Проблема с ресурсами CURL
По цепочке зависимостей, которую удалось установить, тянется пакет guzzle/guzzle: v3.9.0.
В этом пакете используется расширение php-curl. 
В версиях php < 8 функции типа curl_init возвращали ресурс, и в коде самого пакета guzzle/guzzle: v3.9.0 есть много проверок типа
```php
    // В одних местах
    $handler = curl_init();
    ...
    // В других местах 
    if (!is_resource($handler)) {
        throw new Exception();
    }
```
Так как $handler теперь обьект, эти проверки не проходят и выбрасываются исключения.

Единственное решение, которое смог придумать, форкнуть устаревший пакет (уже давно не поддерживается и находится в архиве) guzzle/guzzle
и переписать условия проверки с is_resource на instanceof.

### 2. Проблемы с тестами

Все закомментировал, нужно отдельно разбираться.

#### 2.1. Метод \rollun\test\functional\Callback\Queues\Adapter\SqsAdapterTest::testCreateAdapterWithDeadLetterQueue()
При попытке получить с контейнера сервис DeadLetterQueue::class, выбрасывается исключение. Такой сервис не сконфигурирован.

#### 2.2. Класс \rollun\test\functional\Callback\PidKiller\WorkerManagerTest
В данном классе все тесты вызывают метод \rollun\callback\PidKiller\LinuxPidKiller::ps(), который был удален 25.06.2019

#### 2.3. Класс \rollun\test\unit\Callback\Queues\Adapter\SqsAdapterTest
Закомментировал все тесты

