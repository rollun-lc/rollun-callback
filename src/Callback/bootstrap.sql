CREATE TABLE IF NOT EXISTS `slots` (
  `id` VARCHAR(255) not null primary key,
  `pid` VARCHAR(255),
  `pid_id` VARCHAR(255),
  `worker_manager` VARCHAR(255)
);
