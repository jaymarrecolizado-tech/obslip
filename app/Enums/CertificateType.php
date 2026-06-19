<?php

declare(strict_types=1);

namespace App\Enums;

enum CertificateType: string
{
    case PHYSICAL = 'physical';
    case DIGITAL = 'digital';
    case HYBRID = 'hybrid';

    public function getLabel(): string
    {
        return match ($this) {
            self::PHYSICAL => 'Physical',
            self::DIGITAL => 'Digital',
            self::HYBRID => 'Hybrid',
        };
    }
}