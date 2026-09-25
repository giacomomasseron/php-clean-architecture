<?php

declare(strict_types=1);

namespace GiacomoMasseroni\PHPCleanArchitecture;

use GiacomoMasseroni\PHPCleanArchitecture\Commands\Makes\MakeControllerCommand;
use GiacomoMasseroni\PHPCleanArchitecture\Commands\Makes\MakeEntityCommand;
use GiacomoMasseroni\PHPCleanArchitecture\Commands\Makes\MakeRepositoryCommand;
use GiacomoMasseroni\PHPCleanArchitecture\Commands\Makes\MakeServiceCommand;
use GiacomoMasseroni\PHPCleanArchitecture\Commands\Makes\MakeUseCaseCommand;
use GiacomoMasseroni\PHPCleanArchitecture\Contracts\CommandInterface;
use GiacomoMasseroni\PHPCleanArchitecture\Exceptions\CommandNotFoundException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

final readonly class Application
{
    public string $baseFolder;

    public string $baseNamespace;

    public function __construct()
    {
        if (is_dir('.' . DIRECTORY_SEPARATOR . 'src')) {
            $this->baseFolder = 'src';
        } else {
            $this->baseFolder = 'app';
        }

        $this->baseNamespace = $this->getNamespaceFromComposerFile($this->baseFolder) ?? ucfirst($this->baseFolder);
    }

    /**
     * @param list<string> $argv
     */
    public function run(array $argv): int
    {
        if (!isset($argv[1])) {
            fwrite(STDERR, $this->getUsage());

            return 1;
        }

        $arguments = $this->getArguments($argv);

        try {
            $command = $this->mapCommand($argv);
        } catch (CommandNotFoundException $exception) {
            fwrite(STDERR, $exception->getMessage() . PHP_EOL . PHP_EOL . $this->getUsage());

            return 1;
        }

        $output = [];
        $result = $command->execute($arguments, $output);
        if (count($output) > 0) {
            echo implode(PHP_EOL, $output);
        }
        exit($result);
    }

    /**
     * @param list<string> $argv
     * @throws CommandNotFoundException
     */
    private function mapCommand(array $argv): CommandInterface
    {
        return match ($argv[1]) {
            'install' => new Commands\InstallCommand($this),
            'check' => new Commands\CheckCommand($this),
            'rector' => new Commands\RectorCommand($this),
            'make:entity' => new MakeEntityCommand($this),
            'make:use-case' => new MakeUseCaseCommand($this),
            'make:repository' => new MakeRepositoryCommand($this),
            'make:controller' => new MakeControllerCommand($this),
            'make:service' => new MakeServiceCommand($this),
            default => throw new CommandNotFoundException($argv[1]),
        };

        //return $argv[1] ?? '';
    }

    private function getUsage(): string
    {
        return implode(PHP_EOL, [
            'Usage: vendor/bin/php-clean-architecture <command> [arguments]',
            '',
            'Available commands:',
            '  install          Publish the configuration files to your root folder',
            '  check            Check the architecture levels in your project',
            '  rector           Add the needed interfaces to your classes',
            '  make:entity      Create an Entity',
            '  make:repository  Create a Repository',
            '  make:use-case    Create a UseCase',
            '  make:controller  Create a Controller',
            '  make:service     Create a Service',
        ]) . PHP_EOL;
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

    public function readYesNo(string $message): bool
    {
        while (true) {
            $input = strtolower(trim((string) readline($message)));

            if ($input === 'y' || $input === 'n') {
                return $input === 'y';
            }

            echo "Invalid input. Please enter 'y' or 'n'.\n";
        }
    }

    /**
     * Find the PSR-4 namespace the project's composer.json maps to the given folder
     */
    private function getNamespaceFromComposerFile(string $folder): ?string
    {
        $composerFilePath = '.' . DIRECTORY_SEPARATOR . 'composer.json';
        if (!file_exists($composerFilePath)) {
            return null;
        }

        $composer = json_decode((string) file_get_contents($composerFilePath), true);
        $autoload = is_array($composer) ? ($composer['autoload'] ?? null) : null;
        $psr4 = is_array($autoload) ? ($autoload['psr-4'] ?? null) : null;
        if (!is_array($psr4)) {
            return null;
        }

        foreach ($psr4 as $namespace => $paths) {
            foreach ((array) $paths as $path) {
                if (is_string($path) && trim($path, './\\') === $folder) {
                    return rtrim((string) $namespace, '\\');
                }
            }
        }

        return null;
    }

    public function getBasePath(): string
    {
        return '.' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'giacomomasseron' . DIRECTORY_SEPARATOR . 'php-clean-architecture';
    }

    public function getPathFromConfigFile(string $level): string
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

    public function getNamespaceFromConfigFile(string $level): string
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
