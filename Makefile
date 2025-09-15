.DEFAULT_GOAL: help

INTERACTIVE := $(shell [ -t 0 ] && echo 1 || echo 0)
ifeq ($(INTERACTIVE), 1)
	DOCKER_FLAGS += -t
else
	DOCKER_COMPOSE_RUN_FLAGS += -T
endif

COMPOSER_HOME ?= ${HOME}/.composer
COMPOSER_CLI = docker run $(DOCKER_FLAGS) -i --rm \
	--env COMPOSER_HOME=${COMPOSER_HOME} \
	--volume ${COMPOSER_HOME}:${COMPOSER_HOME} \
	--volume ${PWD}:/app \
	--user $(shell id -u):$(shell id -g) \
	--workdir /app \
	composer:2

DOCKER_COMPOSE_FILE ?= compose.yaml
DOCKER_COMPOSE = docker compose -f $(DOCKER_COMPOSE_FILE)
DOCKER_COMPOSE_RUN = $(DOCKER_COMPOSE) run --rm

.PHONY: $(filter-out vendor, $(shell awk -F: '/^[a-zA-Z0-9_%-]+:/ { print $$1 }' $(MAKEFILE_LIST) | sort | uniq))

help: ## Display this help
	@awk 'BEGIN {FS = ":.* ##"; printf "\n\033[1mUsage:\033[0m\n  make \033[32m<target>\033[0m\n"} /^[a-zA-Z_-]+:.* ## / { printf "  \033[33m%-25s\033[0m %s\n", $$1, $$2 } /^##@/ { printf "\n\033[1m%s\033[0m\n", substr($$0, 5) } ' $(MAKEFILE_LIST)

##@ CLI
composer-cli: ## Composer runtime. See https://getcomposer.org/doc/03-cli.md
	$(COMPOSER_CLI) /bin/sh

##@ Code analysis
static-code-analysis: ## Code analysis
	$(DOCKER_COMPOSE_RUN) --no-deps php ./vendor/bin/phpstan analyse --memory-limit=512M

apply-cs: ## Apply coding standards with PHP CS Fixer
	$(DOCKER_COMPOSE_RUN) --no-deps php ./vendor/bin/php-cs-fixer fix --show-progress=dots --diff --config=.php-cs-fixer.dist.php

##@ RUN
up: ## Start the app
	$(DOCKER_COMPOSE) up -d --remove-orphans

down: ## Stop the app
	$(DOCKER_COMPOSE) down --remove-orphans
##@ TESTS
test: unit-test

unit-test: DOCKER_COMPOSE_FILE=compose.test.yaml
unit-test: ## Run Unit Tests
	$(DOCKER_COMPOSE_RUN)  --no-deps php ./vendor/bin/phpunit $(R) --testsuite=Unit

##@ DATABASE INTERACTIONS
generate-migration: ## Run Doctrine generate empty migration file
	$(DOCKER_COMPOSE_RUN) php bin/console doctrine:migrations:generate

migrate: ## Run Doctrine migrations against db
	$(DOCKER_COMPOSE_RUN) php bin/console doctrine:migrations:migrate
