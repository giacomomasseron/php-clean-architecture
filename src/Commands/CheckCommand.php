<?php

declare(strict_types=1);

namespace GiacomoMasseroni\PHPCleanArchitecture\Commands;

use GiacomoMasseroni\PHPCleanArchitecture\Contracts\CommandInterface;

final class CheckCommand extends BaseCommand implements CommandInterface
{
    /**
     * @param list<string> $arguments
     * @param list<mixed> &$output
     * @return mixed
     */
    public function execute(array $arguments, array &$output): mixed
    {
        exec('vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'deptrac' . (in_array('v', $arguments) ? ' -v' : ''), $output, $resultCode);
        return $resultCode;
    }
}
