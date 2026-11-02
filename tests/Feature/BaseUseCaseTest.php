<?php

declare(strict_types=1);

use GiacomoMasseroni\PHPCleanArchitecture\BaseUseCase;
use GiacomoMasseroni\PHPCleanArchitecture\Contracts\UseCaseExecutorInterface;
use GiacomoMasseroni\PHPCleanArchitecture\Dispatcher;
use GiacomoMasseroni\PHPCleanArchitecture\Events\UseCaseCompletedEvent;
use GiacomoMasseroni\PHPCleanArchitecture\Events\UseCaseStartedEvent;
use GiacomoMasseroni\PHPCleanArchitecture\Exceptions\PHPCleanArchitectureException;

final class EchoArgumentsUseCase extends BaseUseCase
{
    public function handle(mixed ...$arguments): mixed
    {
        return $arguments;
    }
}

final class BaseUseCaseTestExecutor implements UseCaseExecutorInterface {}

final class BaseUseCaseTestReturnsExecutorUseCase extends BaseUseCase
{
    public function handle(mixed ...$arguments): mixed
    {
        return $this->executor;
    }
}

final class BaseUseCaseTestFailingUseCase extends BaseUseCase
{
    public bool $rolledBack = false;

    public function handle(mixed ...$arguments): mixed
    {
        throw new PHPCleanArchitectureException('handle failed');
    }

    public function rollback(): void
    {
        $this->rolledBack = true;
    }
}

final class BaseUseCaseTestRunsAnotherUseCase extends BaseUseCase
{
    public function handle(mixed ...$arguments): mixed
    {
        return EchoArgumentsUseCase::run('inner', ...$arguments);
    }
}

final class BaseUseCaseTestRecursiveUseCase extends BaseUseCase
{
    /**
     * @var list<BaseUseCaseTestRecursiveUseCase>
     */
    public static array $handled = [];

    /**
     * @var list<BaseUseCaseTestRecursiveUseCase>
     */
    public static array $rolledBack = [];

    public function handle(mixed ...$arguments): mixed
    {
        self::$handled[] = $this;

        if ($arguments[0] === 2) {
            throw new PHPCleanArchitectureException('deepest level failed');
        }

        return static::run($arguments[0] + 1);
    }

    public function rollback(): void
    {
        self::$rolledBack[] = $this;
    }
}

test('running a use case dispatches the started and completed events around its result', function () {
    $dispatched = [];
    $onStarted = function (UseCaseStartedEvent $event) use (&$dispatched): void {
        $dispatched[] = 'started';
    };
    $onCompleted = function (UseCaseCompletedEvent $event) use (&$dispatched): void {
        $dispatched[] = 'completed';
    };

    Dispatcher::getInstance()->addListener(UseCaseStartedEvent::class, $onStarted);
    Dispatcher::getInstance()->addListener(UseCaseCompletedEvent::class, $onCompleted);

    try {
        $result = EchoArgumentsUseCase::run('first', 'second');
    } finally {
        Dispatcher::getInstance()->removeListener(UseCaseStartedEvent::class, $onStarted);
        Dispatcher::getInstance()->removeListener(UseCaseCompletedEvent::class, $onCompleted);
    }

    expect($result)->toBe(['first', 'second'])
        ->and($dispatched)->toBe(['started', 'completed']);
});

test('a use case run through actingAs sees that executor in handle', function () {
    $executor = new BaseUseCaseTestExecutor();

    expect(BaseUseCaseTestReturnsExecutorUseCase::actingAs($executor)->run())->toBe($executor);
});

test('calling run on a use case instance runs that instance', function () {
    $useCase = EchoArgumentsUseCase::actingAs(new BaseUseCaseTestExecutor());
    $started = null;
    $completed = null;
    $onStarted = function (UseCaseStartedEvent $event) use (&$started): void {
        $started = $event->useCase;
    };
    $onCompleted = function (UseCaseCompletedEvent $event) use (&$completed): void {
        $completed = $event->useCase;
    };

    Dispatcher::getInstance()->addListener(UseCaseStartedEvent::class, $onStarted);
    Dispatcher::getInstance()->addListener(UseCaseCompletedEvent::class, $onCompleted);

    try {
        $result = $useCase->run('first');
    } finally {
        Dispatcher::getInstance()->removeListener(UseCaseStartedEvent::class, $onStarted);
        Dispatcher::getInstance()->removeListener(UseCaseCompletedEvent::class, $onCompleted);
    }

    expect($result)->toBe(['first'])
        ->and($started)->toBe($useCase)
        ->and($completed)->toBe($useCase);
});

test('an exception thrown by handle rolls back the use case instance that was run', function () {
    $useCase = BaseUseCaseTestFailingUseCase::actingAs(new BaseUseCaseTestExecutor());

    expect(fn () => $useCase->run())->toThrow(PHPCleanArchitectureException::class, 'handle failed');
    expect($useCase->rolledBack)->toBeTrue();
});

test('a use case instance whose run failed runs that instance again', function () {
    $useCase = BaseUseCaseTestFailingUseCase::actingAs(new BaseUseCaseTestExecutor());

    expect(fn () => $useCase->run())->toThrow(PHPCleanArchitectureException::class, 'handle failed');

    $useCase->rolledBack = false;

    expect(fn () => $useCase->run())->toThrow(PHPCleanArchitectureException::class, 'handle failed');
    expect($useCase->rolledBack)->toBeTrue();
});

test('a use case can run another use case statically from handle', function () {
    expect(BaseUseCaseTestRunsAnotherUseCase::run('outer'))->toBe(['inner', 'outer']);
});

test('a use case running itself statically from handle runs each level on its own instance', function () {
    BaseUseCaseTestRecursiveUseCase::$handled = [];
    BaseUseCaseTestRecursiveUseCase::$rolledBack = [];

    expect(fn () => BaseUseCaseTestRecursiveUseCase::run(0))->toThrow(PHPCleanArchitectureException::class, 'deepest level failed');

    [$outer, $middle, $inner] = BaseUseCaseTestRecursiveUseCase::$handled;

    expect(BaseUseCaseTestRecursiveUseCase::$handled)->toHaveCount(3)
        ->and($middle)->not->toBe($outer)
        ->and($inner)->not->toBe($middle)
        ->and($inner)->not->toBe($outer)
        ->and(BaseUseCaseTestRecursiveUseCase::$rolledBack)->toBe([$inner, $middle, $outer]);
});

test('a use case without its own constructor can be instantiated', function () {
    expect((new ReflectionClass(EchoArgumentsUseCase::class))->isInstantiable())->toBeTrue();
    expect((new EchoArgumentsUseCase())->run('a'))->toBe(['a']);
});
