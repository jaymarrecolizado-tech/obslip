<?php

declare(strict_types=1);

namespace App\Enums;

enum EmploymentStatus: string
{
    case Regular = 'regular';
    case Contractual = 'contractual';
    case Coterminous = 'coterminous';
    case JobOrder = 'job_order';

    public function label(): string
    {
        return match ($this) {
            self::Regular => 'Regular',
            self::Contractual => 'Contractual',
            self::Coterminous => 'Coterminous',
            self::JobOrder => 'Job Order',
        };
    }
}
