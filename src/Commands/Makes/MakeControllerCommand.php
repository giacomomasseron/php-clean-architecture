<?php

declare(strict_types=1);

namespace GiacomoMasseroni\PHPCleanArchitecture\Commands\Makes;

use GiacomoMasseroni\PHPCleanArchitecture\Commands\MakeCommand;
use GiacomoMasseroni\PHPCleanArchitecture\Enums\LevelsEnum;

final class MakeControllerCommand extends MakeCommand
{
    /**
     * @param list<string> $arguments
     * @param list<mixed> &$output
     * @return mixed
     */
    public function execute(array $arguments, array &$output): mixed
    {
        if (empty($arguments[0])) {
            echo "Error: specify controller name.\n";
            return 1;
        }

        $controllerName = $arguments[0];
        $directory = $this->app->getPathFromConfigFile(LevelsEnum::CONTROLLERS->value());
        $filePath = $directory . DIRECTORY_SEPARATOR . $controllerName . '.php';
        $nameSpace = $this->app->getNamespaceFromConfigFile(LevelsEnum::CONTROLLERS->value());

        if (!is_dir($directory)) {
            mkdir($directory, 0o777, true);
        }

        if (file_exists($filePath)) {
            echo "Error: file '$filePath' already exists.\n";
            return 1;
        }

        $stubContent = file_get_contents($this->app->getBasePath() . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'controller.stub');

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

        return 0;
    }
}
