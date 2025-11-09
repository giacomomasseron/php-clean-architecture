<?php

declare(strict_types=1);

namespace GiacomoMasseroni\PHPCleanArchitecture\Enums;

enum LevelsEnum
{
    case ENTITIES;
    case REPOSITORIES;
    case USE_CASES;
    case CONTROLLERS;
    case SERVICES;

    public function value(): string
    {
        return match($this) {
            LevelsEnum::ENTITIES => 'entities',
            LevelsEnum::REPOSITORIES => 'repositories',
            LevelsEnum::USE_CASES => 'use_cases',
            LevelsEnum::CONTROLLERS => 'controllers',
            LevelsEnum::SERVICES => 'services',
        };
    }
}
