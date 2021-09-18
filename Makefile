.PHONY: help up down install

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