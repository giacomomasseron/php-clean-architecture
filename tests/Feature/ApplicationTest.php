<?php

declare(strict_types=1);

test('running without a command prints the usage and exits with an error code', function () {
    $result = runCli([]);

    expect($result['exitCode'])->toBe(1)
        ->and($result['stderr'])->toContain('Usage:')
        ->and($result['stderr'])->toContain('make:use-case')
        ->and($result['stdout'] . $result['stderr'])->not->toContain('Warning')
        ->and($result['stdout'] . $result['stderr'])->not->toContain('Fatal error');
});

test('running an unknown command reports it and exits with an error code', function () {
    $result = runCli(['make:usecase']);

    expect($result['exitCode'])->toBe(1)
        ->and($result['stderr'])->toContain('The command make:usecase was not found')
        ->and($result['stderr'])->toContain('Usage:')
        ->and($result['stdout'] . $result['stderr'])->not->toContain('Fatal error')
        ->and($result['stdout'] . $result['stderr'])->not->toContain('Deprecated');
});
