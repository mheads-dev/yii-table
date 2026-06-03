ROWS ?= 100000
BATCH ?= 2000
FORMATS ?= csv,xlsx

help: ## Show the list of available commands with description.
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)
.DEFAULT_GOAL := help

build: ## Build services
	docker compose -f docker/docker-compose.yml -f docker/docker-compose.override.yml --profile all build
up: ## Start services
	docker compose -f docker/docker-compose.yml -f docker/docker-compose.override.yml --profile all up -d
ps: ## List running services
	docker compose -f docker/docker-compose.yml -f docker/docker-compose.override.yml ps
stop: ## Stop running services
	docker compose -f docker/docker-compose.yml -f docker/docker-compose.override.yml --profile all stop
down: ## Stop running services and remove containers, networks and volumes
	docker compose -f docker/docker-compose.yml -f docker/docker-compose.override.yml --profile all down \
	--remove-orphans \
	--volumes
clear: ## Remove all containers, networks, volumes and images
	docker compose -f docker/docker-compose.yml -f docker/docker-compose.override.yml --profile all down \
	--remove-orphans \
	--volumes \
    --rmi all

run: ## Run arbitrary command
	docker compose -f docker/docker-compose.yml -f docker/docker-compose.override.yml --profile php run \
	--rm \
	--entrypoint $(CMD) \
	php

php: ## Run php in container. Example: make php PHP_ARGS="-v"
	docker compose -f docker/docker-compose.yml -f docker/docker-compose.override.yml --profile php run \
	--rm \
	--entrypoint php \
	php $(PHP_ARGS)

test-all: test-unit test-mysql
test-unit: ## Run unit tests
	docker compose -f docker/docker-compose.yml -f docker/docker-compose.override.yml exec php-mysql \
		vendor/bin/phpunit --testsuite Unit $(RUN_ARGS)
test-mysql: ## Run MySQL tests
	docker compose -f docker/docker-compose.yml -f docker/docker-compose.override.yml --profile mysql up -d
	docker compose -f docker/docker-compose.yml -f docker/docker-compose.override.yml exec php-mysql \
		vendor/bin/phpunit --testsuite Mysql $(RUN_ARGS)

psalm: CMD="vendor/bin/psalm --no-cache" ## Run static analysis using Psalm
psalm: run

cs-fixer: CMD="vendor/bin/php-cs-fixer fix" ## Run code-style fixer
cs-fixer: run

perf-smoke: ## Run export pipeline perf smoke (synthetic reader). Params: ROWS=100000 BATCH=2000 FORMATS=csv,xlsx
	docker compose -f docker/docker-compose.yml -f docker/docker-compose.override.yml --profile php run \
	--rm \
	--entrypoint php \
	php tests/perf/export-pipeline-smoke.php --rows=$(ROWS) --batch=$(BATCH) --formats=$(FORMATS)

shell: CMD="bash" ## Open interactive shell
shell: run
