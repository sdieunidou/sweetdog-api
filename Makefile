SHELL := /bin/bash

filter ?=

install:
	composer install
	symfony server:ca:install
	docker-compose build
.PHONY: install

start:
	docker-compose up -d
	symfony local:server:start
.PHONY: start

load-data:
	symfony console doctrine:migrations:migrate -n
	symfony console doctrine:fixtures:load -n
.PHONY: load-data

tests:
	symfony console cache:clear --env=test || true
	symfony console doctrine:database:drop --force --env=test || true
	symfony console doctrine:database:create --env=test
	symfony console doctrine:migrations:migrate -n --env=test
	symfony console doctrine:fixtures:load -n --env=test
	bin/phpunit $(if $(filter),--filter=$(filter),)
.PHONY: tests

lint:
	vendor/bin/php-cs-fixer fix
	vendor/bin/phpstan analyse src/
.PHONY: lint
