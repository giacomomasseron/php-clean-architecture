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

test('a use case can run another use case statically from handle', function () {
    expect(BaseUseCaseTestRunsAnotherUseCase::run('outer'))->toBe(['inner', 'outer']);
});

test('a use case without its own constructor can be instantiated', function () {
    expect((new ReflectionClass(EchoArgumentsUseCase::class))->isInstantiable())->toBeTrue();
    expect((new EchoArgumentsUseCase())->run('a'))->toBe(['a']);
});
