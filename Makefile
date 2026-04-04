SHELL := /bin/bash

# === Variables ===
PHP_BIN  = php
ARTISAN  = $(PHP_BIN) artisan
COMPOSER = composer
PNPM     = pnpm
PINT     = $(PHP_BIN) vendor/bin/pint app/Http/Controllers/CategoryController.php app/Http/Controllers/Controller.php app/Http/Controllers/DashboardController.php app/Http/Controllers/TransactionController.php app/Http/Requests/DestroyCategoryRequest.php app/Http/Requests/DestroyTransactionRequest.php app/Http/Requests/StoreCategoryRequest.php app/Http/Requests/StoreTransactionRequest.php app/Http/Requests/UpdateCategoryRequest.php app/Http/Requests/UpdateTransactionRequest.php app/Models app/Policies app/Services routes/web.php
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

# === Development ===
dev: ## Start all dev servers (artisan + vite)
	$(COMPOSER) run dev

# === Database ===
migrate: ## Run pending migrations
	$(ARTISAN) migrate

migrate-fresh: ## Drop all tables and re-run all migrations
	$(ARTISAN) migrate:fresh

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
