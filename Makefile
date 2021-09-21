.PHONY: help up down install create-hosts run-migrations rollback-migrations tests

help:
	@grep -E '^[a-zA-Z-]+:.*?## .*$$' Makefile | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "[32m%-15s[0m %s\n", $$1, $$2}'

up: ## Turn on the docker environment
	docker-compose up -d

down: ## Turn off the docker environment
	docker-compose down

install: ## Install all dependency to start environment
	bash scripts/install.sh

create-hosts: ## Create local hosts in /etc/hosts
	bash scripts/hosts.sh

run-migrations: ## Run migrations
	docker exec application php artisan migrate --seed

rollback-migrations: ## Rollback migrations
	docker exec application php artisan migrate:rollback

tests: ## Run tests
	docker exec application vendor/bin/phpunit --testdox
