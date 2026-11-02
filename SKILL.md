---
name: php-clean-architecture
description: >
  Teaches AI agents correct patterns for using the giacomomasseron/php-clean-architecture
  PHP package. Use when working with this package to scaffold or review Entities,
  Repositories, UseCases, Controllers, and Services; invoke UseCases with run(), called
  statically or on an injected instance; listen to UseCase events via the Dispatcher
  singleton; run CLI commands (install, check, rector, make:entity, make:repository,
  make:use-case, make:controller, make:service); configure php-clean-architecture.yaml;
  or detect and fix layer-dependency violations caught by deptrac.
license: MIT
compatibility: Requires PHP project using composer package giacomomasseron/php-clean-architecture
metadata:
  author: giacomo masseroni
---

## 1. Layer Rules (never violate these)

Layers can only depend on layers **below** them in this chain:

```
Controller → UseCase → Repository → Entity
                ↓             ↓
             Service       Service
```

| Layer | Can depend on |
|---|---|
| Entity | nothing |
| Repository | Entity, Service |
| UseCase | Repository, Service |
| Controller | UseCase only |
| Service | nothing (third-party wrappers) |

Violations are caught by `vendor/bin/php-clean-architecture check`. Never introduce a dependency that bypasses this chain (e.g., a UseCase importing a Controller, or an Entity importing a Repository).

---

## 2. Marking a Class as Part of a Layer

Every class must implement the correct marker interface from `GiacomoMasseroni\PHPCleanArchitecture\Contracts\`:

| Layer | Interface |
|---|---|
| Entity | `EntityInterface` |
| Repository | `RepositoryInterface` |
| UseCase | `UseCaseInterface` (+ extend `BaseUseCase`) |
| Controller | `ControllerInterface` |
| Service | `ServiceInterface` |

Rector can auto-add these interfaces based on namespace — run `vendor/bin/php-clean-architecture rector`.

---

## 3. Correct Class Shapes

### Entity
```php
use GiacomoMasseroni\PHPCleanArchitecture\Contracts\EntityInterface;

final class User implements EntityInterface
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {}
}
```

### Repository
```php
use GiacomoMasseroni\PHPCleanArchitecture\Contracts\RepositoryInterface;

final class UserRepository implements RepositoryInterface
{
    public function findById(int $id): User { /* ... */ }

    public function create(string $name): User { /* ... */ }
}
```

### UseCase — **always extend `BaseUseCase` AND implement `UseCaseInterface`**

`handle()` must keep the exact signature `handle(mixed ...$arguments): mixed` (a typed `handle(string $name): User` is a fatal error); check the arguments, which arrive as `mixed`, and forward them to a typed private method (forwarding `...$arguments` unchecked fails PHPStan level 9/max). Dependencies go in the constructor:

```php
use GiacomoMasseroni\PHPCleanArchitecture\BaseUseCase;
use GiacomoMasseroni\PHPCleanArchitecture\Contracts\UseCaseInterface;
use InvalidArgumentException;

final class CreateUser extends BaseUseCase implements UseCaseInterface
{
    public function __construct(private readonly UserRepository $users) {}

    public function handle(mixed ...$arguments): mixed
    {
        $name = $arguments[0] ?? null;

        if (!is_string($name)) {
            throw new InvalidArgumentException('CreateUser expects the user name');
        }

        return $this->create($name);
    }

    // mixed, not User: a UseCase must not depend on an Entity (deptrac rule)
    private function create(string $name): mixed
    {
        // business logic here
        return $this->users->create($name);
    }

    // optional: called automatically on PHPCleanArchitectureException
    public function rollback(): void { /* undo side effects */ }
}
```

### Controller — receives the UseCase by injection
```php
use GiacomoMasseroni\PHPCleanArchitecture\Contracts\ControllerInterface;

final class UserController implements ControllerInterface
{
    public function __construct(private readonly CreateUser $createUser) {}

    public function store(string $name): void
    {
        $user = $this->createUser->run($name);
    }
}
```

### Service (third-party wrapper — no logic constraints on its dependencies)
```php
use GiacomoMasseroni\PHPCleanArchitecture\Contracts\ServiceInterface;

final class StripeService implements ServiceInterface
{
    public function charge(int $cents): void { /* ... */ }
}
```

---

## 4. Invoking a UseCase

Always go through `run()` — **never** call `handle()` directly.

**UseCase without constructor dependencies** — call the static `run()` helper:

```php
// simple call
$result = CalculateTotal::run($items);

// with executor (e.g., authenticated user)
$result = CalculateTotal::actingAs($currentUser)->run($items);
```

**UseCase with constructor dependencies** — let the container build it, inject it (see the Controller in §3), and call `run()` on the instance:

```php
$result = $this->createUser->run($name);
$result = ($this->createUser)($name); // same as run()
```

With phpstan-strict-rules, `$this->createUser->run(...)` is reported as `staticMethod.dynamicCall` (the `run()` docblock is static, for `CreateUser::run()`): use the invocation form `($this->createUser)(...)` instead.

Static `CreateUser::run()` and `CreateUser::actingAs()` throw `UseCaseNotStaticallyRunnableException` when the UseCase has required constructor parameters (a private or protected constructor without them is fine). The exception flags a wiring mistake, but it extends `PHPCleanArchitectureException` (code 400): thrown inside another UseCase's `handle()` it triggers that UseCase's `rollback()`, and an app mapping `PHPCleanArchitectureException` to 4xx responses should exclude it. `actingAs()` is static, so on an injected instance set the public `executor` property, or pass the actor as an argument:

```php
$this->createUser->executor = $currentUser;
$result = $this->createUser->run($name);
```

Everything set on an instance persists between runs: the executor, `$data` and any other property an earlier `run()` left on it are still there for the next `run()` and its `rollback()`. This applies to an `actingAs()` instance run more than once and to shared container services (Symfony services are shared by default): pass the actor as an argument instead of setting `executor`, and reset per-run state at the start of `handle()` or declare the UseCase as non-shared.

`run()` (static or on an instance) calls `__invoke()`, which fires `UseCaseStartedEvent` → `handle()` → `UseCaseCompletedEvent` on that same instance.  
`rollback()` is only called when `PHPCleanArchitectureException` is thrown from `handle()`.

---

## 5. Listening to UseCase Events

Add listeners to the singleton `Dispatcher` — do **not** construct it with `new`:

```php
use GiacomoMasseroni\PHPCleanArchitecture\Dispatcher;
use GiacomoMasseroni\PHPCleanArchitecture\Events\UseCaseStartedEvent;
use GiacomoMasseroni\PHPCleanArchitecture\Events\UseCaseCompletedEvent;

Dispatcher::getInstance()->addListener(UseCaseStartedEvent::class, function (UseCaseStartedEvent $event): void {
    // $event->useCase is the UseCase instance
});

Dispatcher::getInstance()->addListener(UseCaseCompletedEvent::class, function (UseCaseCompletedEvent $event): void {
    // log, measure execution time, etc.
});
```

In Laravel, register these in `AppServiceProvider::register()`.

---

## 6. CLI Scaffolding Commands

Run from the consumer project root:

```bash
vendor/bin/php-clean-architecture install           # publish deptrac.yaml, php-clean-architecture.yaml, rector.php
vendor/bin/php-clean-architecture check             # detect layer violations (exits non-zero on violations)
vendor/bin/php-clean-architecture rector            # auto-add interfaces by namespace
vendor/bin/php-clean-architecture rector --dry-run  # preview rector changes without writing

vendor/bin/php-clean-architecture make:entity       OrderItem
vendor/bin/php-clean-architecture make:repository   OrderRepository
vendor/bin/php-clean-architecture make:use-case     PlaceOrder        # note: hyphen, not camel
vendor/bin/php-clean-architecture make:controller   OrderController
vendor/bin/php-clean-architecture make:service      Stripe            # auto-renamed → StripeService
```

All generators read paths and namespaces from `php-clean-architecture.yaml`.

---

## 7. Configuring `php-clean-architecture.yaml`

Level keys must match exactly `entities`, `repositories`, `use_cases`, `controllers`, `services`:

```yaml
php-clean-architecture:
  - levels:
    - entities:
        path: './app/Entities'
        namespace: 'App\Entities'
    - repositories:
        path: './app/Repositories'
        namespace: 'App\Repositories'
    - use_cases:
        path: './app/UseCases'
        namespace: 'App\UseCases'
    - controllers:
        path: './app/Controllers'
        namespace: 'App\Controllers'
    - services:
        path: './app/Services'
        namespace: 'App\Services'
```

`{base_folder}` defaults to `app` (or `src` if a `src/` directory exists) when auto-detected by `Application`.
`{base_namespace}` is the PSR-4 namespace mapped to that folder in the project's `composer.json` (falls back to the capitalized folder name).

---

## 8. Common Mistakes to Avoid

| ❌ Wrong | ✅ Correct |
|---|---|
| `make:usecase` | `make:use-case` |
| `$useCase->handle(...)`: calling `handle()` directly skips the events and `rollback()` | `$useCase->run(...)` |
| `public function handle(string $name): User`: fatal, it must be compatible with `UseCaseInterface::handle(mixed ...$arguments): mixed` | `handle(mixed ...$arguments): mixed` that checks the arguments and forwards them to a typed private method |
| `$this->createUser->run(...)` reported as `staticMethod.dynamicCall` by phpstan-strict-rules | `($this->createUser)(...)` |
| `CreateUser::run(...)` / `CreateUser::actingAs(...)` on a UseCase with required constructor parameters: throws `UseCaseNotStaticallyRunnableException` | Inject the UseCase and call `$this->createUser->run(...)` |
| Running one instance several times (a shared service, or an `actingAs()` instance) and expecting a clean state: the executor, `$data` and other properties persist between runs | Pass the actor as an argument; reset per-run state at the start of `handle()`, or declare the UseCase as non-shared |
| UseCase importing a Controller | Reverse the call direction |
| Entity importing a Repository | Repositories depend on Entities, not the reverse |
| `new Dispatcher()` | `Dispatcher::getInstance()` |
| Service implementing `ServiceInterface` missing in `deptrac.yaml` live config | `deptrac.yaml` uses `classLike: .*Service.*`; `stubs/deptrac.yaml.stub` uses `implements ServiceInterface` — keep them intentionally in sync when modifying |
| Throwing a non-`PHPCleanArchitectureException` and expecting `rollback()` | Only `PHPCleanArchitectureException` triggers `rollback()` |

