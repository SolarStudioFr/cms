.PHONY: install init start stop test log terminal migrate clear

install:
	composer install --working-dir=cms
	npm install
	npm run build
	mkdir -p docker/db/data

init:
	php cms/bin/console doctrine:database:drop --force --if-exists
	php cms/bin/console doctrine:database:create
	php cms/bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
	php cms/bin/console doctrine:fixtures:load --no-interaction

start:
	docker compose up -d --build
	@echo "App:        http://localhost:8000"
	@echo "phpMyAdmin: http://localhost:8080"
	@echo "Mailpit:    http://localhost:8025"

stop:
	docker compose down

test:
	docker compose exec php php cms/bin/console doctrine:database:create --if-not-exists --env=test
	docker compose exec php php cms/bin/console doctrine:migrations:migrate --no-interaction --env=test
	docker compose exec php php cms/bin/console doctrine:fixtures:load --no-interaction --env=test
	docker compose exec php php cms/bin/phpunit -c cms/phpunit.dist.xml

log:
	docker compose logs -f

terminal:
	docker compose exec php sh

migrate:
	docker compose exec php php cms/bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

clear:
	docker compose exec php php cms/bin/console cache:clear
