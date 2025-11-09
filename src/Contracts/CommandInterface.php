<?php

declare(strict_types=1);

namespace GiacomoMasseroni\PHPCleanArchitecture\Contracts;

interface CommandInterface
{
    /**
     * @param list<string> $arguments
     * @param list<mixed> &$output
     * @return mixed
     */
    public function execute(array $arguments, array &$output): mixed;
}
