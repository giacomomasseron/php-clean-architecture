# Changelog

All notable changes to `php-clean-architecture` will be documented in this file.

## v2.0.2 - 2026-09-25

### Features

- Support Symfony 8: `symfony/yaml` and `symfony/event-dispatcher` now accept `^8.0`, so installing the package no longer downgrades Symfony 8 components in your project
- Running `vendor/bin/php-clean-architecture` without a command, or with an unknown one, now prints the available commands instead of crashing

### Fixes

- Projects with a `src/` folder are now detected: `install` configures `src` instead of `app`, and uses the namespace mapped to that folder in your `composer.json` PSR-4 autoload
- Fixed the PHP 8.4 "implicitly nullable parameter" deprecation in `PHPCleanArchitectureException`
- README: the use case command is `make:use-case` (not `make:usecase`)

## v2.0.1 - 2026-08-22

### Features

- Reviewed the dependabot deptrac bump and fixed two issues: removed committed merge-conflict markers from the auto-merge workflow, and loosened deptrac's exact pin to ^4.6 in composer.json

## v2.0.0 - 2026-06-26

### Features

- minimum-stability from dev to stable

## v1.0.2 - 2026-04-22

### Features

- add SKILL.md for AI agents

## v1.0.1 - 2026-02-23

### Features

- Remove singleton pattern for BaseUseCase class

## v1.0.0 - 2025-12-13

### Features

- Stable release

## v0.3.0 - 2025-11-09

### Features

- added `rector` command to add interfaces to your existant project
- code refactored

### Breaking changes

`ServiceInterface` has been added to define classes that belong to Service level.

## v0.2.0 - 2025-10-26

### Features

- Fix run function in BaseUseCase class, to let call the function statically and not statically
- Add dispatcher to dispatch 2 events: `UseCaseStartedEvent` and `UseCaseCompletedEvent`

## v0.1.0 - 2025-10-21

### Features

- `BaseUseCase` refactor
- Update README.md

## v0.0.5 - 2025-10-17

### Features

- Add `make:entity` command to create entity
- Add `make:repository` command to create repository
- Add `make:controller` command to create controller
- Add `make:service` command to create service

## v0.0.4 - 2025-10-17

### Features

- Add `php-clean-architecture.yaml` config file
- Add `make:usecase command` to create use case

## v0.0.3 - 2025-10-15

### Features

- Move deptrac from require-dev to require

## v0.0.2 - 2025-10-14

### Features

- Fix deptrac binary path

## v0.0.1 - 2025-10-10

### Features

- Add levels interfaces
- Add commands to execute CI/CD
