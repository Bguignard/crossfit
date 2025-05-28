#!/bin/env just --justfile
dockup:
    docker compose up -d

cs-fixer:
    docker exec symfony_dockerized-php-1 vendor/bin/php-cs-fixer fix --verbose

doctrine-fixtures:
    docker exec symfony_dockerized-php-1 bin/console doctrine:fixtures:load -n

doctrine-diff:
    docker exec symfony_dockerized-php-1 bin/console doctrine:mi:dif -n

doctrine-migrate:
    docker exec symfony_dockerized-php-1 bin/console doctrine:mi:mi -n

tests:
	docker exec symfony_dockerized-php-1 bin/console doctrine:database:drop --force --env=test || true
	docker exec symfony_dockerized-php-1 bin/console doctrine:database:create --env=test
	docker exec symfony_dockerized-php-1 bin/console doctrine:migrations:migrate -n --env=test
	docker exec symfony_dockerized-php-1 bin/console doctrine:fixtures:load -n --env=test
	docker exec symfony_dockerized-php-1 bin/phpunit
