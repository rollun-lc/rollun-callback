# CHANGELOG.md

## 8.x

- Refactored `rollun\callback\Callback\SerializedCallback` to support `opis/closure` v4 and php8.1. **The serialization
  format has been updated. Deserialization of strings created with earlier versions of this library is not yet
  supported.**
- Add return type hint for `rollun\callback\Callback\Ticker::_invoke`
- Replaced "mtdowling/cron-expression" (deprecated repo) with "dragonmantank/cron-expression" (new repo)