<?php

declare(strict_types=1);

namespace GiacomoMasseroni\PHPCleanArchitecture\Commands;

use GiacomoMasseroni\PHPCleanArchitecture\Contracts\CommandInterface;

abstract class MakeCommand extends BaseCommand implements CommandInterface
{
    /**
     * @param list<string> $arguments
     * @param list<mixed> &$output
     * @return mixed
     */
    abstract public function execute(array $arguments, array &$output): mixed;
}
