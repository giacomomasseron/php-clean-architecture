<?php

declare(strict_types=1);

namespace GiacomoMasseroni\PHPCleanArchitecture\Events;

use GiacomoMasseroni\PHPCleanArchitecture\Contracts\UseCaseInterface;
use Psr\EventDispatcher\StoppableEventInterface;

class Event extends \Symfony\Contracts\EventDispatcher\Event implements StoppableEventInterface
{
    final public function __construct(public UseCaseInterface $useCase) {}
}
