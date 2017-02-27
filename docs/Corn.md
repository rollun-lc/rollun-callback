# CronMultiplexer

Multiplexer создаеться с помощью фабрики `CronMultiplexerFactory`, 
и имеет ряд дополнительных конфигов в отличии от обычных multiplexer. 

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

Тем самым CronMultiplexer отличаеться от обычного Multiplexer тем что умеет скомпоновать два независимых Multiplexer, 
и вызывать их с опредленным периодом.