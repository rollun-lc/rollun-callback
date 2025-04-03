init: docker-down-clear docker-pull docker-build docker-up composer-install
up: docker-up
down: docker-down
restart: docker-down docker-up
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
UID ?= $(shell id -u)
GID ?= $(shell id -g)

export UID
export GID

docker-build:
	docker compose build --build-arg WWW_DATA_UID=$(UID) --build-arg WWW_DATA_GID=$(GID)

composer-install:
	docker compose exec php-fpm composer install

composer-test:
	docker compose run --rm php-fpm composer test

php:
	docker compose exec -it php-fpm /bin/bash

php-root:
	docker compose exec -u root -it php-fpm /bin/bash