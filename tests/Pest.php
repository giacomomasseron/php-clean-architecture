<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

// pest()->extend(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1); /** @phpstan-ignore-line */
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Run the php-clean-architecture binary in a separate process.
 *
 * @param list<string> $arguments
 * @return array{exitCode: int, stdout: string, stderr: string}
 */
function runCli(array $arguments, ?string $workingDirectory = null): array
{
    $process = proc_open(
        [PHP_BINARY, '-d', 'error_reporting=-1', '-d', 'display_errors=stderr', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'php-clean-architecture', ...$arguments],
        [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        $workingDirectory ?? dirname(__DIR__),
    );

    if (!is_resource($process)) {
        throw new RuntimeException('Unable to start the php-clean-architecture process');
    }

    $stdout = (string) stream_get_contents($pipes[1]);
    $stderr = (string) stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    return ['exitCode' => proc_close($process), 'stdout' => $stdout, 'stderr' => $stderr];
}

/**
 * Create a temporary project with the package in its vendor folder, removed when the tests end.
 *
 * @param array<string, string> $files File contents keyed by their path relative to the project root
 */
function createProject(array $files): string
{
    $project = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php-clean-architecture-' . bin2hex(random_bytes(8));
    $package = $project . '/vendor/giacomomasseron/php-clean-architecture';

    mkdir($package . '/stubs', 0o777, true);
    copy(dirname(__DIR__) . '/php-clean-architecture.yaml', $package . '/php-clean-architecture.yaml');
    foreach (glob(dirname(__DIR__) . '/stubs/*.stub') ?: [] as $stub) {
        copy($stub, $package . '/stubs/' . basename($stub));
    }

    foreach ($files as $path => $content) {
        $filePath = $project . '/' . $path;
        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0o777, true);
        }
        file_put_contents($filePath, $content);
    }

    register_shutdown_function(fn () => removeDirectory($project));

    return $project;
}

function removeDirectory(string $directory): void
{
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    /** @var SplFileInfo $item */
    foreach ($items as $item) {
        if ($item->isDir()) {
            rmdir($item->getPathname());
        } else {
            unlink($item->getPathname());
        }
    }

    rmdir($directory);
}
