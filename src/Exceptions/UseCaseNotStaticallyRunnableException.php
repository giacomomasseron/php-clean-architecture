<?php

declare(strict_types=1);

namespace GiacomoMasseroni\PHPCleanArchitecture\Exceptions;

class UseCaseNotStaticallyRunnableException extends PHPCleanArchitectureException
{
    public function __construct(string $useCase)
    {
        parent::__construct('The use case ' . $useCase . ' has constructor dependencies: build it with your container and call run() on the instance', 400);
    }
}
