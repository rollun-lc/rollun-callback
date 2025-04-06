init: docker-down-clear docker-pull docker-build docker-up composer-install
up: docker-up
down: docker-down
restart: docker-down docker-up
check: rector test
test: composer-test

docker-up:
	docker compose up -d

docker-down:
	docker compose down --remove-orphans

docker-down-clear:
	docker compose down -v --remove-orphans

docker-pull:
	docker compose pull

# Set UID and GID dynamically but allow overrides
DOCKER_USER_UID ?= $(shell id -u)
DOCKER_USER_GID ?= $(shell id -g)

docker-build:
	docker compose build

php:
	docker compose exec -it php-fpm /bin/bash

composer-install:
	docker compose exec php-fpm composer install

composer-test:
	docker compose run --rm php-fpm composer test

rector:
	docker compose exec php-fpm composer rector