---
name: php-clean-architecture
description: >
  Teaches AI agents correct patterns for using the giacomomasseron/php-clean-architecture
  PHP package. Use when working with this package to scaffold or review Entities,
  Repositories, UseCases, Controllers, and Services; invoke UseCases via the static
  run() helper; listen to UseCase events via the Dispatcher singleton; run CLI commands
  (install, check, rector, make:entity, make:repository, make:use-case, make:controller,
  make:service); configure php-clean-architecture.yaml; or detect and fix layer-dependency
  violations caught by deptrac.
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
}
```

### UseCase — **always extend `BaseUseCase` AND implement `UseCaseInterface`**
```php
use GiacomoMasseroni\PHPCleanArchitecture\BaseUseCase;
use GiacomoMasseroni\PHPCleanArchitecture\Contracts\UseCaseInterface;

final class CreateUser extends BaseUseCase implements UseCaseInterface
{
    public function handle(string $name): User
    {
        // business logic here
    }

    // optional: called automatically on PHPCleanArchitectureException
    public function rollback(): void { /* undo side effects */ }
}
```

### Controller
```php
use GiacomoMasseroni\PHPCleanArchitecture\Contracts\ControllerInterface;

final class UserController implements ControllerInterface
{
    public function store(string $name): void
    {
        $user = CreateUser::run($name);
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

Always call via the static `run()` helper — do **not** instantiate directly:

```php
// simple call
$result = CreateUser::run($name);

// with executor (e.g., authenticated user)
$result = CreateUser::actingAs($currentUser)->run($name);
```

`run()` internally calls `__invoke()` which fires `UseCaseStartedEvent` → `handle()` → `UseCaseCompletedEvent`.  
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

`{base_folder}` defaults to `app` (or `src` if `.src/` directory exists) when auto-detected by `Application`.

---

## 8. Common Mistakes to Avoid

| ❌ Wrong | ✅ Correct |
|---|---|
| `make:usecase` | `make:use-case` |
| `new CreateUser()->handle(...)` | `CreateUser::run(...)` |
| UseCase importing a Controller | Reverse the call direction |
| Entity importing a Repository | Repositories depend on Entities, not the reverse |
| `new Dispatcher()` | `Dispatcher::getInstance()` |
| Service implementing `ServiceInterface` missing in `deptrac.yaml` live config | `deptrac.yaml` uses `classLike: .*Service.*`; `stubs/deptrac.yaml.stub` uses `implements ServiceInterface` — keep them intentionally in sync when modifying |
| Throwing a non-`PHPCleanArchitectureException` and expecting `rollback()` | Only `PHPCleanArchitectureException` triggers `rollback()` |

