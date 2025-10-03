# Makefile for Orders Management Application

.PHONY: help up down build test logs shell clean

# Default target
help: ## Show this help message
	@echo "Available commands:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

up: ## Start all services
	docker-compose up -d

down: ## Stop all services
	docker-compose down

build: ## Build and start services
	docker-compose up --build -d

test: ## Run tests
	docker-compose exec app ./vendor/bin/phpunit

test-unit: ## Run unit tests only
	docker-compose exec app ./vendor/bin/phpunit tests/Unit

test-functional: ## Run functional tests only
	docker-compose exec app ./vendor/bin/phpunit tests/Functional

logs: ## Show application logs
	docker-compose logs -f app

logs-db: ## Show database logs
	docker-compose logs -f db

logs-all: ## Show all logs
	docker-compose logs -f

shell: ## Access application container
	docker-compose exec app bash

shell-db: ## Access database container
	docker-compose exec db mysql -u root -p

migrate: ## Run database migrations
	docker-compose exec app bin/console doctrine:migrations:migrate

seed-manticore: ## Seed Manticore index
	docker-compose exec app bin/console app:seed-manticore

cache-clear: ## Clear application cache
	docker-compose exec app bin/console cache:clear

install: ## Install dependencies
	docker-compose exec app composer install

clean: ## Clean up containers and volumes
	docker-compose down -v --remove-orphans
	docker system prune -f

status: ## Show services status
	docker-compose ps

restart: ## Restart all services
	docker-compose restart

restart-app: ## Restart application only
	docker-compose restart app

# Code quality commands
cs-fix: ## Fix code style with PHP CS Fixer
	docker-compose exec app ./vendor/bin/php-cs-fixer fix

cs-check: ## Check code style with PHP CS Fixer (dry-run)
	docker-compose exec app ./vendor/bin/php-cs-fixer fix --dry-run --diff

cs-config: ## Show PHP CS Fixer configuration
	docker-compose exec app ./vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --dry-run --diff --verbose

# Development commands
dev: ## Start development environment
	docker-compose up -d
	@echo "Waiting for services to be ready..."
	@sleep 10
	@echo "Running migrations..."
	docker-compose exec app bin/console doctrine:migrations:migrate --no-interaction
	@echo "Seeding Manticore index..."
	docker-compose exec app bin/console app:seed-manticore
	@echo "Development environment is ready!"
	@echo "API Documentation: http://localhost:8080/api/docs"
	@echo "phpMyAdmin: http://localhost:8081"

# Production commands
prod-build: ## Build production images
	docker-compose -f docker-compose.yml -f docker-compose.prod.yml build

prod-up: ## Start production environment
	docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d

prod-down: ## Stop production environment
	docker-compose -f docker-compose.yml -f docker-compose.prod.yml down

# Utility commands
backup-db: ## Backup database
	docker-compose exec db mysqldump -u root -p${DB_PASSWORD:-password} ${DB_NAME:-orders_db} > backup_$(shell date +%Y%m%d_%H%M%S).sql

restore-db: ## Restore database from backup (usage: make restore-db BACKUP=backup_file.sql)
	docker-compose exec -T db mysql -u root -p${DB_PASSWORD:-password} ${DB_NAME:-orders_db} < ${BACKUP}

check-health: ## Check health of all services
	docker-compose ps
	@echo "Checking application health..."
	@curl -f http://localhost:8080/api/docs > /dev/null 2>&1 && echo "✓ Application is healthy" || echo "✗ Application is not responding"
	@echo "Checking database..."
	@docker-compose exec db mysqladmin ping -h localhost -u root -p${DB_PASSWORD:-password} > /dev/null 2>&1 && echo "✓ Database is healthy" || echo "✗ Database is not responding"
	@echo "Checking Redis..."
	@docker-compose exec redis redis-cli ping > /dev/null 2>&1 && echo "✓ Redis is healthy" || echo "✗ Redis is not responding"
	@echo "Checking Manticore..."
	@curl -f http://localhost:9306/status > /dev/null 2>&1 && echo "✓ Manticore is healthy" || echo "✗ Manticore is not responding"
