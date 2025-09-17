.PHONY: build up down logs bash install

build:
	@echo "🔨 Building containers..."
	docker-compose build

up:
	@echo "🚀 Starting containers..."
	docker-compose up --build -d

down:
	@echo "🛑 Stopping containers..."
	docker-compose down

start:
	@echo "▶️ Starting containers..."
	docker-compose start

stop:
	@echo "⏸️ Stopping containers..."
	docker-compose stop

clear-backend-cache:
	@echo "🧹 Clearing Symfony backend cache..."
	docker exec -it symfony_api bash -c "php bin/console cache:clear"

db-init:
	@echo "📦 Initializing database..."
	docker exec -it symfony_api bash -c "php bin/console doctrine:database:create --if-not-exists"
	docker exec -it symfony_api bash -c "php bin/console doctrine:migrations:migrate --no-interaction"

db-force-update:
	@echo "🔄 Forcing database update..."
	docker exec -it symfony_api bash -c "php bin/console doctrine:schema:update --force"

db-security-hash:
	@echo "🔐 Hashing password..."
	docker exec -it symfony_api bash -c "php bin/console security:hash-password '$(password)'"

logs:
	docker-compose logs -f

bash-backend:
	docker exec -it symfony_api bash

bash-frontend:
	docker exec -it vue_frontend sh

install:
	bash scripts/install.sh
	$(MAKE) db-init

rebuild: down build up
