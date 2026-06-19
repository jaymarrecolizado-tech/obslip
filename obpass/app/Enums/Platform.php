<?php

declare(strict_types=1);

namespace App\Enums;

enum Platform: string
{
    case Android = 'android';
    case IOS = 'ios';

    public function label(): string
    {
        return match ($this) {
            self::Android => 'Android',
            self::IOS => 'iOS',
        };
    }
}
