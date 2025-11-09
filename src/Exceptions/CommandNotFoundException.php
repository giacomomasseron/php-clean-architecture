<?php

declare(strict_types=1);

namespace GiacomoMasseroni\PHPCleanArchitecture\Exceptions;

class CommandNotFoundException extends PHPCleanArchitectureException
{
    public function __construct(string $command)
    {
        parent::__construct('The command ' . $command . ' was not found', 400);
    }
}
