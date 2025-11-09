<?php

declare(strict_types=1);

namespace GiacomoMasseroni\PHPCleanArchitecture\Commands;

final class RectorCommand extends MakeCommand
{
    /**
     * @param list<string> $arguments
     * @param list<mixed> &$output
     * @return mixed
     */
    public function execute(array $arguments, array &$output): mixed
    {
        echo "Applying rector rules...\n";

        exec('vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'rector' . (in_array('dryrun', $arguments) ? ' --dry-run' : '') . (in_array('clearcache', $arguments) ? ' --clear-cache' : ''), $output, $resultCode);
        return $resultCode;
    }
}
