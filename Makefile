# load .env
ifneq (,$(wildcard .env))
    include .env
    export
endif

init: docker-down-clear docker-pull docker-build docker-up composer-install
init-8.0: docker-down-clear-8.0 docker-pull-8.0 docker-build-8.0 docker-up-8.0 composer-install-8.0

up: docker-up
up-8.0: docker-up-8.0

down: docker-down
down-8.0: docker-down-8.0

restart: docker-down docker-up
restart-8.0: docker-down-8.0 docker-up-8.0

check: lint rector test

test: composer-test
test-8.0: composer-test-8.0

docker-up:
	docker compose up -d

docker-up-8.0:
	docker compose -f docker-compose-8.0.yml up -d

docker-down:
	docker compose down --remove-orphans

docker-down-8.0:
	docker compose -f docker-compose-8.0.yml down --remove-orphans

docker-down-clear:
	docker compose down -v --remove-orphans

docker-down-clear-8.0:
	docker compose -f docker-compose-8.0.yml down -v --remove-orphans

docker-pull:
	docker compose pull

docker-pull-8.0:
	docker compose -f docker-compose-8.0.yml pull

# Set UID and GID dynamically but allow overrides
DOCKER_USER_UID ?= $(shell id -u)
DOCKER_USER_GID ?= $(shell id -g)

export DOCKER_USER_UID
export DOCKER_USER_GID

docker-build:
	docker compose build --build-arg WWW_DATA_UID=$(DOCKER_USER_UID) --build-arg WWW_DATA_GID=$(DOCKER_USER_GID)

php:
	docker compose exec -it php-fpm /bin/bash

php-root:
	docker compose exec -u root -it php-fpm /bin/bash

docker-build-8.0:
	docker compose -f docker-compose-8.0.yml build

composer-install:
	docker compose exec php-fpm composer install

composer-install-8.0:
	docker compose -f docker-compose-8.0.yml exec php-fpm composer install

composer-test:
	docker compose exec php-fpm composer test

composer-test-8.0:
	docker compose -f docker-compose-8.0.yml exec php-fpm composer test

fixcs:
	 docker compose run --rm csfixer fix

lint:
	 docker compose run --rm csfixer fix --dry-run

rector:
	docker compose exec php-fpm composer rector

rector-fix:
	docker compose exec php-fpm php vendor/bin/rector