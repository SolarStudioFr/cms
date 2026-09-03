.PHONY: install init start stop test log terminal migrate clear

install:
	composer install
	npm install
	npm run build
	mkdir -p docker/db/data

init:
	php bin/console doctrine:database:drop --force --if-exists
	php bin/console doctrine:database:create
	php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

start:
	docker compose up -d --build
	@echo "App:        http://localhost:8000"
	@echo "phpMyAdmin: http://localhost:8080"
	@echo "Mailpit:    http://localhost:8025"

stop:
	docker compose down

test:
	docker compose exec php php bin/phpunit

log:
	docker compose logs -f

terminal:
	docker compose exec php sh

migrate:
	docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

clear:
	docker compose exec php php bin/console cache:clear
