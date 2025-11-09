<?php

declare(strict_types=1);

namespace GiacomoMasseroni\PHPCleanArchitecture\Commands;

use GiacomoMasseroni\PHPCleanArchitecture\Contracts\CommandInterface;
use GiacomoMasseroni\PHPCleanArchitecture\Enums\LevelsEnum;

final class InstallCommand extends BaseCommand implements CommandInterface
{
    /**
     * @param list<string> $arguments
     * @param list<mixed> &$output
     * @return mixed
     */
    public function execute(array $arguments, array &$output): mixed
    {
        echo "Installing PHP Clean Architecture...\n";

        $newDeptracFilePath = '.' . DIRECTORY_SEPARATOR . 'deptrac.yaml';

        if (file_exists($newDeptracFilePath) && ! $this->app->readYesNo("A deptrac.yaml file already exists. Do you want to overwrite it? (y/n): ")) {
            echo "deptrac.yaml file was not overwritten.\n";
        } else {
            echo "Copying deptrac.yaml file to your root directory.\n";

            $this->copyDeptracFile($newDeptracFilePath);
        }
        echo "\n";

        $newConfigFilePath = '.' . DIRECTORY_SEPARATOR . 'php-clean-architecture.yaml';

        if (file_exists($newConfigFilePath) && ! $this->app->readYesNo("A php-clean-architecture.yaml file already exists. Do you want to overwrite it? (y/n): ")) {
            echo "php-clean-architecture.yaml file was not overwritten.\n";
        } else {
            echo "Copying php-clean-architecture.yaml file to your root directory.\n";

            $this->copyConfigFile($newConfigFilePath);
        }
        echo "\n";

        $newRectorFile = '.' . DIRECTORY_SEPARATOR . 'rector.php';

        if (file_exists($newRectorFile) && ! $this->app->readYesNo("A rector.php file already exists. Do you want to overwrite it? (y/n): ")) {
            echo "rector.php file was not overwritten.\n";
            $this->outputRectorInstructions();
        } else {
            echo "Copying rector.php file to your root directory.\n";

            $this->copyRectorFile($newRectorFile);
        }
        echo "\n";

        echo "Done! You can now run 'vendor/bin/php-clean-architecture check' to check your architecture.\n";

        return 0;
    }

    private function copyDeptracFile(string $newPath): void
    {
        $this->updateDeptracPath($this->app->baseFolder, $newPath);
    }

    private function updateDeptracPath(string $deptracPath, string $newPath): void
    {
        $filePath = $this->app->getBasePath() . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'deptrac.yaml.stub';
        $content = file_get_contents($filePath);
        if ($content !== false) {
            $newContent = str_replace('{deptrac_path}', $deptracPath, $content);
            file_put_contents($newPath, $newContent);
        }
    }

    private function copyConfigFile(string $newPath): void
    {
        $this->updateConfigPath($this->app->baseFolder, $newPath);
    }

    private function updateConfigPath(string $deptracPath, string $newPath): void
    {
        $filePath = $this->app->getBasePath() . DIRECTORY_SEPARATOR . 'php-clean-architecture.yaml';
        $content = file_get_contents($filePath);
        if ($content !== false) {
            $newContent = str_replace(['{base_folder}', '{base_namespace}'], [$deptracPath, ucfirst($deptracPath)], $content);
            file_put_contents($newPath, $newContent);
        }
    }

    private function copyRectorFile(string $newPath): void
    {
        $this->updateRectorPath($this->app->baseFolder, $newPath);
    }

    private function updateRectorPath(string $rectorPath, string $newPath): void
    {
        $filePath = $this->app->getBasePath() . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'rector.php.stub';

        $controllersNamespace = $this->app->getNamespaceFromConfigFile(LevelsEnum::CONTROLLERS->value());
        $useCasesNamespace = $this->app->getNamespaceFromConfigFile(LevelsEnum::USE_CASES->value());
        $repositoriesNamespace = $this->app->getNamespaceFromConfigFile(LevelsEnum::REPOSITORIES->value());
        $entitiesNamespace = $this->app->getNamespaceFromConfigFile(LevelsEnum::ENTITIES->value());
        $servicesNamespace = $this->app->getNamespaceFromConfigFile(LevelsEnum::SERVICES->value());

        $content = file_get_contents($filePath);
        if ($content !== false) {
            $newContent = str_replace(
                [
                    '{{base_path}}',
                    '{{controllers_namespace}}',
                    '{{use_cases_namespace}}',
                    '{{repositories_namespace}}',
                    '{{entities_namespace}}',
                    '{{services_namespace}}',
                ],
                [
                    $rectorPath,
                    $controllersNamespace,
                    $useCasesNamespace,
                    $repositoriesNamespace,
                    $entitiesNamespace,
                    $servicesNamespace,
                ],
                $content
            );
            file_put_contents($newPath, $newContent);
        }
    }

    private function outputRectorInstructions(): void
    {
        $controllersNamespace = $this->app->getNamespaceFromConfigFile(LevelsEnum::CONTROLLERS->value());
        $useCasesNamespace = $this->app->getNamespaceFromConfigFile(LevelsEnum::USE_CASES->value());
        $repositoriesNamespace = $this->app->getNamespaceFromConfigFile(LevelsEnum::REPOSITORIES->value());
        $entitiesNamespace = $this->app->getNamespaceFromConfigFile(LevelsEnum::ENTITIES->value());
        $servicesNamespace = $this->app->getNamespaceFromConfigFile(LevelsEnum::SERVICES->value());

        echo "\n" . "Check your rector.php file and in case, add these rules to it:\n";
        echo "\033[00;32m ";
        echo <<<EOT
            ----------------------------------------------------------------
            |
            | Code to add to the rector.php file
            |
            -----------------------------------------------------------------
            ->withConfiguredRule(
                \GiacomoMasseroni\PHPCleanArchitecture\Rector\Rules\AddPHPCleanArchitectureInterfaceControllerToClassesRector::class,
                [
                    'targetNamespaces' => [
                        '{$controllersNamespace}',
                    ]
                ]
            )
            ->withConfiguredRule(
                \GiacomoMasseroni\PHPCleanArchitecture\Rector\Rules\AddPHPCleanArchitectureInterfaceEntityToClassesRector::class,
                [
                    'targetNamespaces' => [
                        '{$entitiesNamespace}',
                    ]
                ]
            )
            ->withConfiguredRule(
                \GiacomoMasseroni\PHPCleanArchitecture\Rector\Rules\AddPHPCleanArchitectureInterfaceRepositoryToClassesRector::class,
                [
                    'targetNamespaces' => [
                        '{$repositoriesNamespace}',
                    ]
                ]
            )
            ->withConfiguredRule(
                \GiacomoMasseroni\PHPCleanArchitecture\Rector\Rules\AddPHPCleanArchitectureInterfaceServiceToClassesRector::class,
                [
                    'targetNamespaces' => [
                        '{$servicesNamespace}',
                    ]
                ]
            )
            ->withConfiguredRule(
                \GiacomoMasseroni\PHPCleanArchitecture\Rector\Rules\AddPHPCleanArchitectureInterfaceUseCaseToClassesRector::class,
                [
                    'targetNamespaces' => [
                        '{$useCasesNamespace}',
                    ]
                ]
            )

            EOT;
        echo "\033[0m";
    }
}
