SHELL := /bin/bash
PHP_VERSION ?= 8.2
IMAGE_NAME = otel-auto-class
DOCKER_RUN = docker run --rm -t -v $(CURDIR):/app $(IMAGE_NAME):$(PHP_VERSION)

.PHONY: $(wildcard *)

## help: Show available commands
help:
	@echo "Usage:"
	@sed -n 's/^## //p' Makefile | awk -F': ' '{printf "  %-20s %s\n", $$1, $$2}'

## build: Build Docker image
build:
	@if ! docker image inspect $(IMAGE_NAME):$(PHP_VERSION) >/dev/null 2>&1; then \
		echo "Building $(IMAGE_NAME):$(PHP_VERSION)..."; \
		docker build --build-arg PHP_VERSION=$(PHP_VERSION) -t $(IMAGE_NAME):$(PHP_VERSION) docker/; \
	fi

## install: Vendor dependencies
vendor: build
	@echo "Vendoring dependencies..."
	@rm -rf vendor
	@$(DOCKER_RUN) composer update --no-interaction --quiet

## fix: Fix code
fix: build
	@echo "Fixing code..."
	@$(DOCKER_RUN) vendor/bin/php-cs-fixer fix --show-progress=none

## lint: Lint code
lint: build
	@echo "Linting code..."
	@$(DOCKER_RUN) vendor/bin/php-cs-fixer fix --dry-run --diff --show-progress=none
	@$(DOCKER_RUN) vendor/bin/phpstan analyse --no-progress

## test: Test code
test: build
	@echo "Testing code..."
	@$(DOCKER_RUN) php -dxdebug.mode=coverage vendor/bin/phpunit --no-progress --coverage-text --coverage-html coverage

## bench: Bench code
bench: build
	@echo "Benching code..."
	@$(DOCKER_RUN) vendor/bin/phpbench run --report=default --progress=none

## audit: Vendor, fix, lint, test, bench
audit: vendor fix lint test bench
