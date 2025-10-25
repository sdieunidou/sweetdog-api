SHELL := /bin/bash

tests:
	symfony console doctrine:database:drop --force --env=test || true
	symfony console doctrine:database:create --env=test
	symfony console doctrine:migrations:migrate -n --env=test
	symfony php bin/phpunit $(MAKECMDGOALS)
.PHONY: tests

lint:
	vendor/bin/php-cs-fixer fix src/
	vendor/bin/php-cs-fixer fix tests/
	vendor/bin/phpstan analyse src/
.PHONY: lint