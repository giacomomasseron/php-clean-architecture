# Changelog

All notable changes to `php-clean-architecture` will be documented in this file.

## v2.1.1 - 2026-11-02

### Fixes

- A use case with a private or protected constructor (without required parameters) can be run with the static `run()` and `actingAs()`: they threw `ReflectionException: Access to non-public constructor`
- README and SKILL.md: the `CreateUser` example checks its argument before passing it to the typed method, so it passes PHPStan level 9 and max
- README and SKILL.md: with phpstan-strict-rules, call an injected use case as `($useCase)(...)`, because `$useCase->run(...)` is reported as `staticMethod.dynamicCall`
- README and SKILL.md: `UseCaseNotStaticallyRunnableException` reports a mistake in your code; it triggers the calling use case's `rollback()` and should not become a 4xx response

## v2.1.0 - 2026-11-02

### Features

- Use cases can declare their dependencies in the constructor: build them with your container (Laravel, Symfony, …) or with `new`, and call `$useCase->run(...)` or `$useCase(...)`, with the same events and rollback as the static `run()`
- `run()` can be called on a use case instance, and runs that instance
- New `UseCaseNotStaticallyRunnableException`, thrown by the static `run()` and `actingAs()` on a use case with required constructor parameters: build it with your container and call `run()` on the instance

### Fixes

- `actingAs($user)->run()` keeps the executor: `handle()` and the event listeners now see it, it was always `null`
- `rollback()` and the events act on the instance the caller holds, not on a copy of it
- A static `run()` builds one instance instead of two
- SKILL.md: the UseCase example declared `handle(string $name): User`, a fatal error; it now keeps `handle(mixed ...$arguments): mixed` and forwards to a typed private method

### Upgrade notes

- Calling `run()` more than once on the same instance (one returned by `actingAs()`, or a shared container service) now reuses that instance: the executor, `$data` and any other property set by an earlier run are still there for the next run and its `rollback()`. In 2.0.2 each run started from a new instance.

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
