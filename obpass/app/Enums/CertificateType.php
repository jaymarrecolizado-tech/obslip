<?php

declare(strict_types=1);

namespace App\Enums;

enum CertificateType: string
{
    case Physical = 'physical';
    case Digital = 'digital';
    case Hybrid = 'hybrid';

    public function label(): string
    {
        return match ($this) {
            self::Physical => 'Physical',
            self::Digital => 'Digital',
            self::Hybrid => 'Hybrid',
        };
    }
}
