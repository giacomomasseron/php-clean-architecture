<?php

declare(strict_types=1);

namespace GiacomoMasseroni\PHPCleanArchitecture\Commands\Makes;

use GiacomoMasseroni\PHPCleanArchitecture\Commands\MakeCommand;
use GiacomoMasseroni\PHPCleanArchitecture\Enums\LevelsEnum;

final class MakeEntityCommand extends MakeCommand
{
    /**
     * @param list<string> $arguments
     * @param list<mixed> &$output
     * @return mixed
     */
    public function execute(array $arguments, array &$output): mixed
    {
        if (empty($arguments[0])) {
            echo "Error: specify entity name.\n";
            return 1;
        }

        $entityName = $arguments[0];
        $directory = $this->app->getPathFromConfigFile('entities');
        $filePath = $directory . DIRECTORY_SEPARATOR . $entityName . '.php';
        $nameSpace = $this->app->getNamespaceFromConfigFile(LevelsEnum::ENTITIES->value());

        if (!is_dir($directory)) {
            mkdir($directory, 0o777, true);
        }

        if (file_exists($filePath)) {
            echo "Error: file '$filePath' already exists.\n";
            return 1;
        }

        $stubContent = file_get_contents($this->app->getBasePath() . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'entity.stub');

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

        return 0;
    }
}
