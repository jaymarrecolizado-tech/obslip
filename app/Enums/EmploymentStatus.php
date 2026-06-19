<?php

declare(strict_types=1);

namespace App\Enums;

enum EmploymentStatus: string
{
    case REGULAR = 'regular';
    case CONTRACTUAL = 'contractual';
    case COTERMINOUS = 'coterminous';
    case JOB_ORDER = 'job_order';

    public function getLabel(): string
    {
        return match ($this) {
            self::REGULAR => 'Regular',
            self::CONTRACTUAL => 'Contractual',
            self::COTERMINOUS => 'Coterminous',
            self::JOB_ORDER => 'Job Order',
        };
    }
}