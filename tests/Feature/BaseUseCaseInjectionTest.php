<?php

declare(strict_types=1);

use GiacomoMasseroni\PHPCleanArchitecture\BaseUseCase;
use GiacomoMasseroni\PHPCleanArchitecture\Dispatcher;
use GiacomoMasseroni\PHPCleanArchitecture\Events\UseCaseCompletedEvent;
use GiacomoMasseroni\PHPCleanArchitecture\Events\UseCaseStartedEvent;
use GiacomoMasseroni\PHPCleanArchitecture\Exceptions\UseCaseNotStaticallyRunnableException;

final class InjectionTestDependency
{
    public function value(): string
    {
        return 'from the dependency';
    }
}

final class InjectionTestUseCase extends BaseUseCase
{
    public function __construct(private readonly InjectionTestDependency $dependency) {}

    public function handle(mixed ...$arguments): mixed
    {
        return [$this->dependency->value(), ...$arguments];
    }
}

final class InjectionTestDefaultDependencyUseCase extends BaseUseCase
{
    public function __construct(private readonly InjectionTestDependency $dependency = new InjectionTestDependency()) {}

    public function handle(mixed ...$arguments): mixed
    {
        return [$this->dependency->value(), ...$arguments];
    }
}

test('a use case built with its constructor dependencies runs with them and dispatches its events', function () {
    $useCase = new InjectionTestUseCase(new InjectionTestDependency());
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
        $result = $useCase->run('a');
    } finally {
        Dispatcher::getInstance()->removeListener(UseCaseStartedEvent::class, $onStarted);
        Dispatcher::getInstance()->removeListener(UseCaseCompletedEvent::class, $onCompleted);
    }

    expect($result)->toBe(['from the dependency', 'a'])
        ->and($started)->toBe($useCase)
        ->and($completed)->toBe($useCase);
});

test('invoking a use case built with its constructor dependencies gives the same result as running it', function () {
    $useCase = new InjectionTestUseCase(new InjectionTestDependency());

    expect($useCase('a'))->toBe(['from the dependency', 'a']);
});

test('a use case with constructor dependencies cannot be run statically', function () {
    expect(fn () => InjectionTestUseCase::run('a'))->toThrow(
        UseCaseNotStaticallyRunnableException::class,
        'The use case InjectionTestUseCase has constructor dependencies: build it with your container and call run() on the instance',
    );
});

test('a use case with constructor dependencies cannot be built by actingAs', function () {
    expect(fn () => InjectionTestUseCase::actingAs(null))->toThrow(
        UseCaseNotStaticallyRunnableException::class,
        'The use case InjectionTestUseCase has constructor dependencies: build it with your container and call run() on the instance',
    );
});

test('a use case whose constructor dependencies all have defaults can still be run statically', function () {
    expect(InjectionTestDefaultDependencyUseCase::run('a'))->toBe(['from the dependency', 'a']);
});
