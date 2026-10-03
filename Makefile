COMPOSE := docker compose

.DEFAULT_GOAL := help
.PHONY: help up down logs test test-filter shell artisan tinker fresh pint npm build

help: ## Show available targets
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-12s\033[0m %s\n", $$1, $$2}'

up: ## Build and start everything (API on :8000, Vite on :5173)
	$(COMPOSE) up --build -d
	@echo ""
	@echo "  App:  http://localhost:8000"
	@echo "  Logs: make logs"

down: ## Stop containers
	$(COMPOSE) down

logs: ## Tail container logs
	$(COMPOSE) logs -f --tail=100

test: ## Run the full test suite
	$(COMPOSE) exec app php artisan test

test-filter: ## Run tests matching F=<name>, e.g. make test-filter F=delivery
	$(COMPOSE) exec app php artisan test --filter="$(F)"

shell: ## Open a shell in the app container
	$(COMPOSE) exec app bash

artisan: ## Run an artisan command, e.g. make artisan A="route:list"
	$(COMPOSE) exec app php artisan $(A)

tinker: ## Open tinker
	$(COMPOSE) exec app php artisan tinker

fresh: ## Drop, migrate and reseed the database
	$(COMPOSE) exec app php artisan migrate:fresh --seed

pint: ## Format PHP code
	$(COMPOSE) exec app vendor/bin/pint

npm: ## Run an npm command in the vite container, e.g. make npm A="install react"
	$(COMPOSE) exec vite npm $(A)

build: ## Build production frontend assets into public/build
	$(COMPOSE) exec vite npm run build
