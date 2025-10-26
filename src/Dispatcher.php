<?php

declare(strict_types=1);

namespace GiacomoMasseroni\PHPCleanArchitecture;

use Symfony\Component\EventDispatcher\EventDispatcher;

final class Dispatcher extends EventDispatcher
{
    /** @var static $instance */
    private static EventDispatcher $instance;

    final private function __construct()
    {
        parent::__construct();
    }

    public static function getInstance(): static
    {
        if (!isset(self::$instance)) {
            self::$instance = new static();
        }

        return self::$instance;
    }
}
