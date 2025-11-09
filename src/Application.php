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
     * @throws CommandNotFoundException
     */
    public function run(array $argv): int
    {
        $arguments = $this->getArguments($argv);
        $command = $this->mapCommand($argv);

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
