<?php

declare(strict_types=1);

namespace GiacomoMasseroni\PHPCleanArchitecture\Commands\Makes;

use GiacomoMasseroni\PHPCleanArchitecture\Commands\MakeCommand;
use GiacomoMasseroni\PHPCleanArchitecture\Enums\LevelsEnum;

final class MakeServiceCommand extends MakeCommand
{
    /**
     * @param list<string> $arguments
     * @param list<mixed> &$output
     * @return mixed
     */
    public function execute(array $arguments, array &$output): mixed
    {
        if (empty($arguments[0])) {
            echo "Error: specify service name.\n";
            return 1;
        }

        $serviceName = $arguments[0];
        // Add "Service" if it doesn't already end with "Service"
        if (!str_ends_with($serviceName, "Service")) {
            $serviceName .= "Service";
        }

        $directory = $this->app->getPathFromConfigFile(LevelsEnum::SERVICES->value());
        $filePath = $directory . DIRECTORY_SEPARATOR . $serviceName . '.php';
        $nameSpace = $this->app->getNamespaceFromConfigFile(LevelsEnum::SERVICES->value());

        if (!is_dir($directory)) {
            mkdir($directory, 0o777, true);
        }

        if (file_exists($filePath)) {
            echo "Error: file '$filePath' already exists.\n";
            return 1;
        }

        $stubContent = file_get_contents($this->app->getBasePath() . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'service.stub');

        if ($stubContent !== false) {
            $content = str_replace(
                [
                    '{{namespace}}',
                    '{{serviceName}}',
                ],
                [
                    $nameSpace,
                    $serviceName,
                ],
                $stubContent
            );
            file_put_contents($filePath, $content);

            echo "Service created: $filePath\n";
        }

        return 0;
    }
}
