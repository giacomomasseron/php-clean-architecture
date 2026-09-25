<?php

declare(strict_types=1);

use Symfony\Component\Yaml\Yaml;

test('classes are created in the src folder with the namespace composer maps to it', function () {
    $project = createProject([
        'composer.json' => '{"autoload": {"psr-4": {"Acme\\\\": "src/"}}}',
        'src/.gitkeep' => '',
    ]);

    expect(runCli(['install'], $project)['exitCode'])->toBe(0)
        ->and(runCli(['make:entity', 'User'], $project)['exitCode'])->toBe(0)
        ->and($project . '/src/Entities/User.php')->toBeFile()
        ->and((string) file_get_contents($project . '/src/Entities/User.php'))->toContain('namespace Acme\Entities;')
        ->and($project . '/app')->not->toBeDirectory();
});

test('install points deptrac at the src folder', function () {
    $project = createProject([
        'composer.json' => '{"autoload": {"psr-4": {"Acme\\\\": "src/"}}}',
        'src/.gitkeep' => '',
    ]);

    runCli(['install'], $project);

    expect(Yaml::parseFile($project . '/deptrac.yaml'))->toHaveKey('deptrac.paths.0', './src');
});

test('classes are created in the app folder when the project has no src folder', function () {
    $project = createProject([
        'composer.json' => '{"autoload": {"psr-4": {"App\\\\": "app/"}}}',
    ]);

    expect(runCli(['install'], $project)['exitCode'])->toBe(0)
        ->and(runCli(['make:entity', 'User'], $project)['exitCode'])->toBe(0)
        ->and($project . '/app/Entities/User.php')->toBeFile()
        ->and((string) file_get_contents($project . '/app/Entities/User.php'))->toContain('namespace App\Entities;');
});

test('install works in a src project without a composer.json', function () {
    $project = createProject([
        'src/.gitkeep' => '',
    ]);

    $install = runCli(['install'], $project);

    expect($install['exitCode'])->toBe(0)
        ->and($install['stdout'] . $install['stderr'])->not->toContain('Warning')
        ->and(runCli(['make:entity', 'User'], $project)['exitCode'])->toBe(0)
        ->and($project . '/src/Entities/User.php')->toBeFile();
});
