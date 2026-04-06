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

# === Laravel Cache (Production) ===
cc-prod: ## Clear and regenerate all production caches
	@echo "Clearing and regenerating production caches..."
	$(ARTISAN) config:cache
	$(ARTISAN) route:cache
	$(ARTISAN) view:cache
	$(ARTISAN) event:cache
	@sudo systemctl reload php8.4-fpm 2>/dev/null && echo "✅ PHP-FPM reloaded (opcache cleared)" || echo "⚠️  PHP-FPM reload skipped (not available)"
	@echo "✅ Production caches regenerated successfully"

# === Queue Worker ===
# TODO: create the systemd service file /etc/systemd/system/spendly-queue.service on the production server
#       it should run: php artisan queue:work --sleep=3 --tries=3 --max-time=3600
queue-start: ## Start the Queue worker via systemctl
	@sudo systemctl start spendly-queue 2>/dev/null && echo "✅ Queue worker started" || echo "❌ Failed to start Queue worker (service may not exist)"

queue-stop: ## Stop the Queue worker via systemctl
	@sudo systemctl stop spendly-queue 2>/dev/null && echo "✅ Queue worker stopped" || echo "⚠️  Queue worker not stopped (service may not exist)"

queue-restart: ## Restart the Queue worker via systemctl
	@sudo systemctl restart spendly-queue 2>/dev/null && echo "✅ Queue worker restarted" || echo "❌ Failed to restart Queue worker (service may not exist)"

queue-status: ## Show the status of the Queue worker via systemctl
	@sudo systemctl status spendly-queue 2>/dev/null || echo "⚠️  Queue worker service not found"

queue-logs: ## Show the last 50 lines of Queue worker logs
	@sudo journalctl -u spendly-queue -n 50 --no-pager || echo "⚠️  Could not retrieve logs"

stop-and-wait-queue: ## Stop Queue worker and wait until fully stopped (max 30s)
	@echo "Stopping Queue worker..."; \
	$(ARTISAN) queue:restart 2>/dev/null || true; \
	sudo systemctl stop spendly-queue 2>/dev/null || true; \
	echo "Waiting for spendly-queue to fully stop..."; \
	i=0; \
	while [ "$$(systemctl is-active spendly-queue 2>/dev/null)" = "active" ] && [ $$i -lt 30 ]; do \
		sleep 1; i=$$((i+1)); \
	done; \
	if [ $$i -ge 30 ]; then \
		echo "⚠️  spendly-queue still active after 30s, continuing anyway"; \
	else \
		echo "✅ spendly-queue stopped"; \
	fi

setup-perms: ## Fix storage and cache permissions (usage: FULL_PERMS=1 make setup-perms)
	chmod +x ./setup-perms.sh 2>/dev/null || true
	sudo ./setup-perms.sh

# === Deployment ===
deploy-prod: ## Deploy to production (requires git tag on HEAD, use FORCE=1 to bypass up-to-date check)
	@mkdir -p storage/logs
	@LOG_FILE=storage/logs/deploy-prod.log; \
	MAX_LOG_SIZE=10485760; \
	if [ -f $$LOG_FILE ] && [ $$(stat -f%z $$LOG_FILE 2>/dev/null || stat -c%s $$LOG_FILE 2>/dev/null || echo 0) -gt $$MAX_LOG_SIZE ]; then \
		tail -n 1000 $$LOG_FILE > $${LOG_FILE}.tmp && mv $${LOG_FILE}.tmp $$LOG_FILE; \
		echo "=== Log rotated on $$(date) ===" >> $$LOG_FILE; \
	fi; \
	echo "=== Deployment started on $$(date) ===" | tee -a $$LOG_FILE; \
	if [ "$$FULL_PERMS" = "1" ] || [ "$$FULL_PERMS" = "true" ]; then \
		echo "⚠️  Full permissions mode enabled (slower but more thorough)"; \
		make setup-perms FULL_PERMS=1 2>&1 | tee -a $$LOG_FILE; \
	else \
		echo "Using fast permissions mode (writable directories only)"; \
		make setup-perms 2>&1 | tee -a $$LOG_FILE; \
	fi; \
	echo "Checking for updates..."; \
	echo "Resetting any changes in vendor/ directory..."; \
	git checkout -- vendor/ 2>/dev/null || true; \
	git restore vendor/ 2>/dev/null || true; \
	GIT_OUTPUT=$$(git pull && git fetch --tags 2>&1); \
	GIT_EXIT=$$?; \
	echo "$$GIT_OUTPUT" | tee -a $$LOG_FILE; \
	if [ "$$FORCE" != "1" ] && [ "$$FORCE" != "true" ] && [ $$GIT_EXIT -eq 0 ] && echo "$$GIT_OUTPUT" | grep -q "Already up to date"; then \
		echo "✅ Repository is already up to date. Skipping deployment." | tee -a $$LOG_FILE; \
		echo "💡 Tip: Use 'FORCE=1 make deploy-prod' to force deployment even if up to date." | tee -a $$LOG_FILE; \
		echo "=== Deployment skipped (no changes) on $$(date) ===" | tee -a $$LOG_FILE; \
		echo "" | tee -a $$LOG_FILE; \
		exit 0; \
	fi; \
	if [ "$$FORCE" = "1" ] || [ "$$FORCE" = "true" ]; then \
		echo "⚠️  Force mode enabled: continuing deployment even if repository is up to date." | tee -a $$LOG_FILE; \
	fi; \
	if [ $$GIT_EXIT -ne 0 ]; then \
		echo "❌ Git pull failed with exit code $$GIT_EXIT" | tee -a $$LOG_FILE; \
		echo "=== Deployment failed on $$(date) ===" | tee -a $$LOG_FILE; \
		exit $$GIT_EXIT; \
	fi; \
	make stop-and-wait-queue 2>&1 | tee -a $$LOG_FILE; \
	set -o pipefail; \
	{ \
		APP_VERSION=$$(git describe --exact-match --tags HEAD 2>/dev/null); \
		if [ -z "$$APP_VERSION" ]; then \
			echo "❌ Deployment blocked: no release tag on current commit. Create a GitHub release first."; \
			exit 1; \
		fi; \
		echo "$$APP_VERSION" > VERSION; \
		$(COMPOSER) install --no-dev --optimize-autoloader; \
		make cc-prod; \
		$(ARTISAN) migrate --force; \
		$(PNPM) install --frozen-lockfile; \
		$(PNPM) build; \
	} 2>&1 | tee -a $$LOG_FILE; \
	DEPLOY_EXIT=$$?; \
	echo "Restarting Queue worker to use new code..." | tee -a $$LOG_FILE; \
	sudo systemctl restart spendly-queue 2>/dev/null || echo "⚠️  Queue worker not restarted (may not exist)" | tee -a $$LOG_FILE; \
	if [ $$DEPLOY_EXIT -eq 0 ]; then \
		echo "✅ Deployment completed! Check worker status with: sudo systemctl status spendly-queue" | tee -a $$LOG_FILE; \
		echo "=== Deployment completed on $$(date) ===" | tee -a $$LOG_FILE; \
	else \
		echo "❌ Deployment failed with exit code $$DEPLOY_EXIT" | tee -a $$LOG_FILE; \
		echo "⚠️  Queue worker has been restarted to ensure application continues running." | tee -a $$LOG_FILE; \
		echo "=== Deployment failed on $$(date) ===" | tee -a $$LOG_FILE; \
	fi; \
	echo "" | tee -a $$LOG_FILE; \
	exit $$DEPLOY_EXIT

logs-deploy: ## Show deployment logs (tail -f)
	@if [ -f storage/logs/deploy-prod.log ]; then \
		tail -f storage/logs/deploy-prod.log; \
	else \
		echo "Log file not found: storage/logs/deploy-prod.log"; \
	fi

logs-deploy-last: ## Show last 50 lines of deployment logs
	@if [ -f storage/logs/deploy-prod.log ]; then \
		tail -n 50 storage/logs/deploy-prod.log; \
	else \
		echo "Log file not found: storage/logs/deploy-prod.log"; \
	fi


.PHONY: help
help: ## Show this help message
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}'
