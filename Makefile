SHELL := /bin/bash

filter ?=

tests:
	symfony console cache:clear --env=test || true
	symfony console doctrine:database:drop --force --env=test || true
	symfony console doctrine:database:create --env=test
	symfony console doctrine:migrations:migrate -n --env=test
	symfony console doctrine:fixtures:load -n --env=test
	bin/phpunit $(if $(filter),--filter=$(filter),)
.PHONY: tests

lint:
	vendor/bin/php-cs-fixer fix src/
	vendor/bin/php-cs-fixer fix tests/
	vendor/bin/phpstan analyse src/
.PHONY: lint