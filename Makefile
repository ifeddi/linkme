.PHONY: build up down destroy logs bash install

build:
	@echo "🔨 Building containers..."
	docker-compose build

up:
	@echo "🚀 Starting containers..."
	docker-compose up --build -d

down:
	@echo "🛑 Stopping containers..."
	docker-compose down

destroy:
	@echo "🧨 Destroying containers, images and volumes..."
	docker-compose down --rmi all -v --remove-orphans

start:
	@echo "▶️ Starting containers..."
	docker-compose start

stop:
	@echo "⏸️ Stopping containers..."
	docker-compose stop

clear-backend-cache:
	@echo "🧹 Clearing Symfony backend cache..."
	docker exec -it symfony_api bash -c "if [ ! -f vendor/autoload.php ]; then composer install --no-interaction --prefer-dist; fi; php bin/console cache:clear"

db-init:
	@echo "📦 Initializing database..."
	docker exec -it symfony_api bash -c "if [ ! -f vendor/autoload.php ]; then composer install --no-interaction --prefer-dist; fi; php bin/console doctrine:database:create --if-not-exists"
	docker exec -it symfony_api bash -c "php bin/console doctrine:migrations:migrate --no-interaction"

migrate:
	@echo "🗂️ Running migrations..."
	# Optional: pass a specific migration version with `make migrate version=YYYYMMDD...`
	docker exec -it symfony_api bash -c "if [ ! -f vendor/autoload.php ]; then composer install --no-interaction --prefer-dist; fi; if [ -n '$(version)' ]; then php bin/console doctrine:migrations:migrate $(version) --no-interaction; else php bin/console doctrine:migrations:migrate --no-interaction; fi"

db-force-update:
	@echo "🔄 Forcing database update..."
	docker exec -it symfony_api bash -c "if [ ! -f vendor/autoload.php ]; then composer install --no-interaction --prefer-dist; fi; php bin/console doctrine:schema:update --force"

db-security-hash:
	@echo "🔐 Hashing password..."
	docker exec -it symfony_api bash -c "if [ ! -f vendor/autoload.php ]; then composer install --no-interaction --prefer-dist; fi; php bin/console security:hash-password '$(password)'"

logs:
	docker-compose logs -f

bash-backend:
	docker exec -it symfony_api bash

bash-frontend:
	docker exec -it vue_frontend sh

install:
	bash scripts/install.sh
	$(MAKE) up
	$(MAKE) db-init

rebuild: down build up
