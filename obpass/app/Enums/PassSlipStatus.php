<?php

declare(strict_types=1);

namespace App\Enums;

enum PassSlipStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Returned = 'returned';
    case Approved = 'approved';
    case Departed = 'departed';
    case Arrived = 'arrived';
    case CertificateSubmitted = 'certificate_submitted';
    case Verified = 'verified';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::Returned => 'Returned',
            self::Approved => 'Approved',
            self::Departed => 'Departed',
            self::Arrived => 'Arrived',
            self::CertificateSubmitted => 'Certificate Submitted',
            self::Verified => 'Verified',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Submitted => 'info',
            self::Returned => 'warning',
            self::Approved => 'success',
            self::Departed => 'primary',
            self::Arrived => 'success',
            self::CertificateSubmitted => 'info',
            self::Verified => 'success',
            self::Completed => 'success',
            self::Cancelled => 'danger',
        };
    }

    public function canBeCancelled(): bool
    {
        return in_array($this, [self::Draft, self::Submitted]);
    }

    public function canBeApproved(): bool
    {
        return $this === self::Submitted;
    }

    public function canBeDeparted(): bool
    {
        return $this === self::Approved;
    }

    public function canBeArrived(): bool
    {
        return $this === self::Departed;
    }
}
