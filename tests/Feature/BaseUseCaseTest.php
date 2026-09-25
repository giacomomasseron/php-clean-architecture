<?php

declare(strict_types=1);

use GiacomoMasseroni\PHPCleanArchitecture\BaseUseCase;
use GiacomoMasseroni\PHPCleanArchitecture\Dispatcher;
use GiacomoMasseroni\PHPCleanArchitecture\Events\UseCaseCompletedEvent;
use GiacomoMasseroni\PHPCleanArchitecture\Events\UseCaseStartedEvent;

final class EchoArgumentsUseCase extends BaseUseCase
{
    public function handle(mixed ...$arguments): mixed
    {
        return $arguments;
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
