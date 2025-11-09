<?php

declare(strict_types=1);

namespace GiacomoMasseroni\PHPCleanArchitecture\Commands;

use GiacomoMasseroni\PHPCleanArchitecture\Application;

abstract class BaseCommand
{
    public function __construct(
        protected Application $app
    ) {}

    /**
     * @param list<string> $arguments
     * @param list<mixed> &$output
     * @return mixed
     */
    abstract public function execute(array $arguments, array &$output): mixed;
}
