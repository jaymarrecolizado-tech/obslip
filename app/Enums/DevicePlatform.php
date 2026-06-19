<?php

declare(strict_types=1);

namespace App\Enums;

enum DevicePlatform: string
{
    case ANDROID = 'android';
    case IOS = 'ios';

    public function getLabel(): string
    {
        return match ($this) {
            self::ANDROID => 'Android',
            self::IOS => 'iOS',
        };
    }
}