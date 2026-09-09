# CHANGELOG.md

## 8.x

- Fixed a race in `Queues\Adapter\DbAdapter::getMessages()` that could hand the same message to two consumers at once
  (Trello nIjO2cqy). Candidate ids are prefetched without a lock and then claimed with `FOR UPDATE SKIP LOCKED`; a
  competing consumer that claimed and committed one of those ids in between left the row unlocked, so it was claimed
  again and `receive_count` was bumped twice. The availability conditions (`time_in_flight`, `delayed_until`,
  `receive_count`, priority) are now re-evaluated inside the locking read. The locking read is also ordered by
  `added_at`, so the messages returned out of the candidate set are the oldest ones.

- Refactored `rollun\callback\Callback\SerializedCallback` to support `opis/closure` v4 and php8.1. **The serialization
  format has been updated. Deserialization of strings created with earlier versions of this library is not yet
  supported.**
- Add return type hint for `rollun\callback\Callback\Ticker::_invoke`
- Replaced "mtdowling/cron-expression" (deprecated repo) with "dragonmantank/cron-expression" (new repo)