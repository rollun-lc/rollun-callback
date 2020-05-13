## Известные проблемы

Библиотека reputation-vip/queue-client в своих зависимостях указывает aws/aws-sdk-php версии не выше 3, 
а 3-и версии aws/aws-sdk-php используют уже новый пакет guzzle, 
который находится в другом пространстве имен guzzlehttp/guzzle. 

Из-за этого могут возникнуть проблемы с версиями зависимых пакетов.

Порядок зависимостей пакетов:

1. "reputation-vip/queue-client"
 
2. "aws/aws-sdk-php",
 
3. "guzzle/guzzle"
 
4. "symfony/event-dispatcher": "~2.1" 
 
 При возникновении данной проблемы с зависимостями пакетов 
 фиксится указанием в composer.json проекта конкретной версии symfony/event-dispatcher:
```json
"require": {
  "symfony/event-dispatcher": "2.8.52"
}
```