<?php

declare(strict_types=1);

namespace GiacomoMasseroni\PHPCleanArchitecture;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

final readonly class Application
{
    private string $baseFolder;

    public function __construct()
    {
        if (file_exists('.src/')) {
            $this->baseFolder = 'src';
        } else {
            $this->baseFolder = 'app';
        }
    }

    /**
     * @param list<string> $argv
     */
    public function run(array $argv): int
    {
        $command = $this->mapCommand($argv);
        $arguments = $this->getArguments($argv);

        switch ($command) {
            case 'install':
                $this->install($arguments);
                exit(0);

            case 'check':
                $output = [];
                $result = $this->check($arguments, $output);
                echo implode(PHP_EOL, $output);
                exit($result);

            case 'make:entity':
                $this->createEntity($arguments);
                exit(0);

            case 'make:use-case':
                $this->createUseCase($arguments);
                exit(0);

            case 'make:repository':
                $this->createRepository($arguments);
                exit(0);

            case 'make:controller':
                $this->createController($arguments);
                exit(0);

            case 'make:service':
                $this->createService($arguments);
                exit(0);
        }

        exit(1);
    }

    /**
     * @param list<string> $argv
     */
    private function mapCommand(array $argv): string
    {
        return $argv[1] ?? '';
    }

    /**
     * @param list<string> $argv
     * @return list<string>
     */
    private function getArguments(array $argv): array
    {
        return array_map(function (string $arg) {
            return str_replace('-', '', $arg);
        }, array_slice($argv, 2));
    }

    /**
     * @param list<string> $arguments
     */
    private function install(array $arguments): void
    {
        echo "Installing PHP Clean Architecture...\n";

        $newDeptracFilePath = '.' . DIRECTORY_SEPARATOR . 'deptrac.yaml';

        if (file_exists($newDeptracFilePath) && ! $this->readYesNo()) {
            echo "deptrac.yaml file was not overwritten.\n";
        } else {
            echo "Copying deptrac.yaml file to your root directory.\n";

            $this->copyDeptracFile($newDeptracFilePath);
        }

        $newConfigFilePath = '.' . DIRECTORY_SEPARATOR . 'php-clean-architecture.yaml';

        if (file_exists($newConfigFilePath) && ! $this->readYesNo()) {
            echo "php-clean-architecture.yaml file was not overwritten.\n";
        } else {
            echo "Copying php-clean-architecture.yaml file to your root directory.\n";

            $this->copyConfigFile($newConfigFilePath);
        }

        echo "Done! You can now run 'vendor/bin/php-clean-architecture check' to check your architecture.\n";
    }

    /**
     * @param list<string> $arguments
     * @param list<mixed> $output
     * @return int
     */
    private function check(array $arguments, array &$output): int
    {
        exec('vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'deptrac' . (in_array('v', $arguments) ? ' -v' : ''), $output, $resultCode);
        return $resultCode;
    }

    private function copyDeptracFile(string $newPath): void
    {
        $this->updateDeptracPath($this->baseFolder, $newPath);
    }

    private function updateDeptracPath(string $deptracPath, string $newPath): void
    {
        $filePath = $this->getBasePath() . DIRECTORY_SEPARATOR . 'deptrac.yaml';
        $content = file_get_contents($filePath);
        if ($content !== false) {
            $newContent = str_replace('{deptrac_path}', $deptracPath, $content);
            file_put_contents($newPath, $newContent);
        }
    }

    private function copyConfigFile(string $newPath): void
    {
        $this->updateConfigPath($this->baseFolder, $newPath);
    }

    private function updateConfigPath(string $deptracPath, string $newPath): void
    {
        $filePath = $this->getBasePath() . DIRECTORY_SEPARATOR . 'php-clean-architecture.yaml';
        $content = file_get_contents($filePath);
        if ($content !== false) {
            $newContent = str_replace(['{base_folder}', '{base_namespace}'], [$deptracPath, ucfirst($deptracPath)], $content);
            file_put_contents($newPath, $newContent);
        }
    }

    private function readYesNo(): bool
    {
        while (true) {
            $input = strtolower(trim((string) readline("A deptrac.yaml file already exists. Do you want to overwrite it? (y/n): ")));

            if ($input === 'y' || $input === 'n') {
                return $input === 'y';
            }

            echo "Invalid input. Please enter 'y' or 'n'.\n";
        }
    }

    private function getBasePath(): string
    {
        return '.' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'giacomomasseron' . DIRECTORY_SEPARATOR . 'php-clean-architecture';
    }

    /**
     * @param list<string> $arguments
     * @return void
     */
    private function createEntity(array $arguments): void
    {
        if (empty($arguments[0])) {
            echo "Error: specify entity name.\n";
            return;
        }

        $entityName = $arguments[0];
        $directory = $this->getPathFromConfigFile('entities');
        $filePath = $directory . DIRECTORY_SEPARATOR . $entityName . '.php';
        $nameSpace = $this->getNamespaceFromConfigFile('entities');

        if (!is_dir($directory)) {
            mkdir($directory, 0o777, true);
        }

        if (file_exists($filePath)) {
            echo "Error: file '$filePath' already exists.\n";
            return;
        }

        $stubContent = file_get_contents($this->getBasePath() . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'entity.stub');

        if ($stubContent !== false) {
            $content = str_replace(
                [
                    '{{namespace}}',
                    '{{entityName}}',
                ],
                [
                    $nameSpace,
                    $entityName,
                ],
                $stubContent
            );
            file_put_contents($filePath, $content);

            echo "Entity created: $filePath\n";
        }
    }

    /**
     * @param list<string> $arguments
     * @return void
     */
    private function createUseCase(array $arguments): void
    {
        if (empty($arguments[0])) {
            echo "Error: specify use case name.\n";
            return;
        }

        $useCaseName = $arguments[0];
        $directory = $this->getPathFromConfigFile('use_cases');
        $filePath = $directory . DIRECTORY_SEPARATOR . $useCaseName . '.php';
        $nameSpace = $this->getNamespaceFromConfigFile('use_cases');

        if (!is_dir($directory)) {
            mkdir($directory, 0o777, true);
        }

        if (file_exists($filePath)) {
            echo "Error: file '$filePath' already exists.\n";
            return;
        }

        $stubContent = file_get_contents($this->getBasePath() . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'use-case.stub');

        if ($stubContent !== false) {
            $content = str_replace(
                [
                    '{{namespace}}',
                    '{{useCaseName}}',
                ],
                [
                    $nameSpace,
                    $useCaseName,
                ],
                $stubContent
            );
            file_put_contents($filePath, $content);

            echo "Use case created: $filePath\n";
        }
    }

    /**
     * @param list<string> $arguments
     * @return void
     */
    private function createRepository(array $arguments): void
    {
        if (empty($arguments[0])) {
            echo "Error: specify repository name.\n";
            return;
        }

        $repositoryName = $arguments[0];
        $directory = $this->getPathFromConfigFile('repositories');
        $filePath = $directory . DIRECTORY_SEPARATOR . $repositoryName . '.php';
        $nameSpace = $this->getNamespaceFromConfigFile('repositories');

        if (!is_dir($directory)) {
            mkdir($directory, 0o777, true);
        }

        if (file_exists($filePath)) {
            echo "Error: file '$filePath' already exists.\n";
            return;
        }

        $stubContent = file_get_contents($this->getBasePath() . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'repository.stub');

        if ($stubContent !== false) {
            $content = str_replace(
                [
                    '{{namespace}}',
                    '{{repositoryName}}',
                ],
                [
                    $nameSpace,
                    $repositoryName,
                ],
                $stubContent
            );
            file_put_contents($filePath, $content);

            echo "Use case created: $filePath\n";
        }
    }

    /**
     * @param list<string> $arguments
     * @return void
     */
    private function createController(array $arguments): void
    {
        if (empty($arguments[0])) {
            echo "Error: specify repository name.\n";
            return;
        }

        $controllerName = $arguments[0];
        $directory = $this->getPathFromConfigFile('controllers');
        $filePath = $directory . DIRECTORY_SEPARATOR . $controllerName . '.php';
        $nameSpace = $this->getNamespaceFromConfigFile('controllers');

        if (!is_dir($directory)) {
            mkdir($directory, 0o777, true);
        }

        if (file_exists($filePath)) {
            echo "Error: file '$filePath' already exists.\n";
            return;
        }

        $stubContent = file_get_contents($this->getBasePath() . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'controller.stub');

        if ($stubContent !== false) {
            $content = str_replace(
                [
                    '{{namespace}}',
                    '{{controllerName}}',
                ],
                [
                    $nameSpace,
                    $controllerName,
                ],
                $stubContent
            );
            file_put_contents($filePath, $content);

            echo "Controller created: $filePath\n";
        }
    }

    /**
     * @param list<string> $arguments
     * @return void
     */
    private function createService(array $arguments): void
    {
        if (empty($arguments[0])) {
            echo "Error: specify service name.\n";
            return;
        }

        $serviceName = $arguments[0];
        // Add "Service" if it doesn't already end with "Service"
        if (!str_ends_with($serviceName, "Service")) {
            $serviceName .= "Service";
        }
        $directory = $this->getPathFromConfigFile('services');
        $filePath = $directory . DIRECTORY_SEPARATOR . $serviceName . '.php';
        $nameSpace = $this->getNamespaceFromConfigFile('services');

        if (!is_dir($directory)) {
            mkdir($directory, 0o777, true);
        }

        if (file_exists($filePath)) {
            echo "Error: file '$filePath' already exists.\n";
            return;
        }

        $stubContent = file_get_contents($this->getBasePath() . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'controller.stub');

        if ($stubContent !== false) {
            $content = str_replace(
                [
                    '{{namespace}}',
                    '{{controllerName}}',
                ],
                [
                    $nameSpace,
                    $serviceName,
                ],
                $stubContent
            );
            file_put_contents($filePath, $content);

            echo "Controller created: $filePath\n";
        }
    }

    private function getPathFromConfigFile(string $level): string
    {
        $configFilePath = '.' . DIRECTORY_SEPARATOR . 'php-clean-architecture.yaml';
        if (file_exists($configFilePath)) {
            try {
                /**
                 * @var array{
                 *     php-clean-architecture: array{
                 *      0: array{
                 *          levels: array{
                 *              0: array{
                 *                  entities: array{
                 *                      path: string,
                 *                      namespace: string
                 *                  }
                 *              },
                 *              1: array{
                 *                  repositories: array{
                 *                      path: string,
                 *                      namespace: string
                 *                  }
                 *              },
                 *              2: array{
                 *                  use_cases: array{
                 *                      path: string,
                 *                      namespace: string
                 *                  }
                 *              },
                 *              3: array{
                 *                  controllers: array{
                 *                      path: string,
                 *                      namespace: string
                 *                  }
                 *              },
                 *              3: array{
                 *                  services: array{
                 *                      path: string,
                 *                      namespace: string
                 *                  }
                 *              }
                 *          }
                 *      }
                 *     }
                 * } $config
                 */
                $config = Yaml::parseFile($configFilePath);
                if (isset($config['php-clean-architecture'][0]['levels'])) {
                    foreach ((array) $config['php-clean-architecture'][0]['levels'] as $levelConfig) {
                        if (isset($levelConfig[$level]['path'])) {
                            return $levelConfig[$level]['path'];
                        }
                    }
                }
            } catch (ParseException $exception) {
                printf('Unable to parse the YAML file. Error: %s', $exception->getMessage());
            }
        }

        return '';
    }

    private function getNamespaceFromConfigFile(string $level): string
    {
        $configFilePath = '.' . DIRECTORY_SEPARATOR . 'php-clean-architecture.yaml';
        if (file_exists($configFilePath)) {
            try {
                /**
                 * @var array{
                 *     php-clean-architecture: array{
                 *      0: array{
                 *          levels: array{
                 *              0: array{
                 *                  entities: array{
                 *                      path: string,
                 *                      namespace: string
                 *                  }
                 *              },
                 *              1: array{
                 *                  repositories: array{
                 *                      path: string,
                 *                      namespace: string
                 *                  }
                 *              },
                 *              2: array{
                 *                  use_cases: array{
                 *                      path: string,
                 *                      namespace: string
                 *                  }
                 *              },
                 *              3: array{
                 *                  controllers: array{
                 *                      path: string,
                 *                      namespace: string
                 *                  }
                 *              },
                 *              3: array{
                 *                  services: array{
                 *                      path: string,
                 *                      namespace: string
                 *                  }
                 *              }
                 *          }
                 *      }
                 *     }
                 * } $config
                 */
                $config = Yaml::parseFile($configFilePath);
                if (isset($config['php-clean-architecture'][0]['levels'])) {
                    foreach ((array) $config['php-clean-architecture'][0]['levels'] as $levelConfig) {
                        if (isset($levelConfig[$level]['namespace'])) {
                            return $levelConfig[$level]['namespace'];
                        }
                    }
                }
            } catch (ParseException $exception) {
                printf('Unable to parse the YAML file. Error: %s', $exception->getMessage());
            }
        }

        return '';
    }
}
