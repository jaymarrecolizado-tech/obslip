<?php

declare(strict_types=1);

namespace App\Enums;

enum PassSlipStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case RETURNED = 'returned';
    case APPROVED = 'approved';
    case DEPARTED = 'departed';
    case ARRIVED = 'arrived';
    case CERTIFICATE_SUBMITTED = 'certificate_submitted';
    case VERIFIED = 'verified';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SUBMITTED => 'Submitted',
            self::RETURNED => 'Returned',
            self::APPROVED => 'Approved',
            self::DEPARTED => 'Departed',
            self::ARRIVED => 'Arrived',
            self::CERTIFICATE_SUBMITTED => 'Certificate Submitted',
            self::VERIFIED => 'Verified',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::SUBMITTED => 'blue',
            self::RETURNED => 'orange',
            self::APPROVED => 'green',
            self::DEPARTED => 'purple',
            self::ARRIVED => 'indigo',
            self::CERTIFICATE_SUBMITTED => 'cyan',
            self::VERIFIED => 'teal',
            self::COMPLETED => 'emerald',
            self::CANCELLED => 'red',
        };
    }

    public function canTransitionTo(PassSlipStatus $status): bool
    {
        $transitions = [
            self::DRAFT => [self::SUBMITTED, self::CANCELLED],
            self::SUBMITTED => [self::RETURNED, self::APPROVED, self::CANCELLED],
            self::RETURNED => [self::SUBMITTED, self::CANCELLED],
            self::APPROVED => [self::DEPARTED, self::CANCELLED],
            self::DEPARTED => [self::ARRIVED],
            self::ARRIVED => [self::CERTIFICATE_SUBMITTED, self::COMPLETED],
            self::CERTIFICATE_SUBMITTED => [self::VERIFIED],
            self::VERIFIED => [self::COMPLETED],
            self::COMPLETED => [],
            self::CANCELLED => [],
        ];

        return in_array($status, $transitions[$this] ?? [], true);
    }
}