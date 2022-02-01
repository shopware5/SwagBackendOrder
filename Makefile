.DEFAULT_GOAL := help

filter := "default"
dirname := $(notdir $(CURDIR))
envprefix := $(shell echo "$(dirname)" | tr A-Z a-z)
envname := $(envprefix)test

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'
.PHONY: help

init: composer-install install-hooks install-plugin install-test-environment ## install the plugin with pre commit hook, requirements and the test environment

composer-install: ## Install composer requirements
	@echo "Install composer requirements"
	composer install

install-hooks: ## Install pre commit hooks
	@echo "Install pre commit hooks"
	.githooks/install_hooks.sh

install-plugin: .refresh-plugin-list ## Install and activate the plugin
	@echo "Install the plugin"
	./../../../bin/console sw:plugin:install $(dirname) --activate -c

install-test-environment: ## Installs the plugin test environment
	@echo "Install the test environment"
	./psh local:init

run-tests: ## Execute the php unit tests... (You can use the filter parameter "make run-tests filter=yourFilterPhrase")
ifeq ($(filter), "default")
	SHOPWARE_ENV=$(envname) ./../../../vendor/phpunit/phpunit/phpunit --verbose
else
	SHOPWARE_ENV=$(envname) ./../../../vendor/phpunit/phpunit/phpunit --verbose --filter $(filter)
endif

CS_FIXER_RUN=
fix-cs: ## Run the code style fixer
	./../../../vendor/bin/php-cs-fixer fix -v $(CS_FIXER_RUN)

fix-cs-dry: CS_FIXER_RUN= --dry-run
fix-cs-dry: fix-cs  ## Run the code style fixer in dry mode

ESLINT_FIX=
check-eslint-backend:
	./../../../themes/node_modules/eslint/bin/eslint.js --ignore-path .eslintignore -c ./../../../themes/Backend/.eslintrc.js Resources/views/backend $(ESLINT_FIX)

fix-eslint-backend: ESLINT_FIX= --fix
fix-eslint-backend: check-eslint-backend

phpstan: ## Run PHPStan
	./../../../vendor/bin/phpstan analyse .

phpstan-generate-baseline: ## Run PHPStan and generate a baseline file
	./../../../vendor/bin/phpstan analyse . --generate-baseline

.refresh-plugin-list:
	@echo "Refresh the plugin list"
	./../../../bin/console sw:plugin:refresh
