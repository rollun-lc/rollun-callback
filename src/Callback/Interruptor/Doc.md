1) ничего не возвращать - void

- id процесса мы можем получить используя пложенные про

interruptor должен возвращать 

* ресур с которого можно считать вывод
* статус работы

* id - interruptor (процесса)
        Не будет  работать для всх interruptor
        обязан вклюбчать идентификацию машины.
        
* data - возможное поле ответа callback
    * может включать встебя результат вложенного callback.
Вложенные interruptor

Поиск ошибок.
Получение логов от interruptor


проверить что вернет curl при успешном и при неуспешном
    {"data":{"PHPUnit_Framework_TestCase":"PHPUnit_Framework_TestCase","PHPUnit_Framework_Assert":"PHPUnit_Framework_Assert"},"status":"complete"}
    В случае ошибки сбрасыавает exception