SHELL := /bin/bash

# === Variables ===
PHP_BIN  = php
ARTISAN  = $(PHP_BIN) artisan
COMPOSER = composer
PNPM     = pnpm
PINT     = $(PHP_BIN) vendor/bin/pint app/ routes/
PHPSTAN  = $(PHP_BIN) tools/phpstan/vendor/bin/phpstan
RECTOR   = $(PHP_BIN) tools/rector/vendor/bin/rector

# === Install ===
install: ## Install all dependencies (composer + tools + npm)
	$(COMPOSER) install
	$(COMPOSER) install --working-dir=tools/phpstan
	$(COMPOSER) install --working-dir=tools/rector
	$(PNPM) install

update: ## Update all dependencies
	$(COMPOSER) update
	$(COMPOSER) update --working-dir=tools/phpstan
	$(COMPOSER) update --working-dir=tools/rector

# === Docker ===
docker-up: ## Start all Docker containers (mailer)
	@echo "Starting all Docker containers..."
	@if command -v docker-compose >/dev/null 2>&1 || command -v docker >/dev/null 2>&1; then \
		if command -v docker-compose >/dev/null 2>&1; then \
			docker-compose up -d && echo "✓ All Docker containers started" || echo "⚠ Failed to start Docker containers"; \
		else \
			docker compose up -d && echo "✓ All Docker containers started" || echo "⚠ Failed to start Docker containers"; \
		fi \
	else \
		echo "⚠ Docker not available, please install Docker Desktop"; \
	fi

docker-down: ## Stop all Docker containers
	@echo "Stopping all Docker containers..."
	@if command -v docker-compose >/dev/null 2>&1 || command -v docker >/dev/null 2>&1; then \
		if command -v docker-compose >/dev/null 2>&1; then \
			docker-compose down && echo "✓ All Docker containers stopped" || echo "⚠ Failed to stop Docker containers"; \
		else \
			docker compose down && echo "✓ All Docker containers stopped" || echo "⚠ Failed to stop Docker containers"; \
		fi \
	else \
		echo "⚠ Docker not available"; \
	fi

# === Development ===
dev: ## Start all dev servers (artisan + vite)
	$(COMPOSER) run dev

start: ## Start mailcatcher + dev servers
	@echo "Starting mailcatcher (if available)..."
	@if command -v docker-compose >/dev/null 2>&1 || command -v docker >/dev/null 2>&1; then \
		if command -v docker-compose >/dev/null 2>&1; then \
			docker-compose up -d mailer 2>/dev/null && echo "✓ Mailcatcher started" || echo "⚠ Mailcatcher not available"; \
		else \
			docker compose up -d mailer 2>/dev/null && echo "✓ Mailcatcher started" || echo "⚠ Mailcatcher not available"; \
		fi \
	else \
		echo "⚠ Docker not available, skipping mailcatcher"; \
	fi
	$(COMPOSER) run dev

stop: ## Stop mailcatcher
	@if command -v docker-compose >/dev/null 2>&1 || command -v docker >/dev/null 2>&1; then \
		if command -v docker-compose >/dev/null 2>&1; then \
			docker-compose stop mailer 2>/dev/null || true; \
		else \
			docker compose stop mailer 2>/dev/null || true; \
		fi \
	fi
	@echo "✓ Mailcatcher stopped"

# === Database ===
migrate: ## Run pending migrations
	$(ARTISAN) migrate

migrate-fresh: ## Drop all tables and re-run all migrations
	$(ARTISAN) migrate:fresh

fixtures: ## Drop all tables, re-run migrations and seed
	$(ARTISAN) migrate:fresh --seed

# === Tests ===
test: ## Run all PHPUnit tests
	$(ARTISAN) test

test-feature: ## Run feature tests only
	$(ARTISAN) test tests/Feature

test-unit: ## Run unit tests only
	$(ARTISAN) test tests/Unit

# === Code Quality ===
stan: ## Run PHPStan static analysis
	$(PHPSTAN) analyse -c tools/phpstan/phpstan.neon --memory-limit 1G

lint-php: ## Check PHP code style (dry-run)
	$(PINT) --test

lint-js: ## Check JS/Vue code style
	$(PNPM) run lint

rector: ## Run Rector (dry-run)
	$(RECTOR) process --dry-run -c tools/rector/rector.php

fix-php: ## Fix PHP code style with Pint
	$(PINT)

fix-js: ## Fix JS/Vue code style
	$(PNPM) run lint:fix

fix-rector: ## Apply Rector changes
	$(RECTOR) process -c tools/rector/rector.php

fix: ## Run all fixers then static analysis
	make fix-rector
	make fix-php
	make fix-js
	make stan


.PHONY: help
help: ## Show this help message
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}'
