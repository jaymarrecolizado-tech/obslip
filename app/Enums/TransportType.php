<?php

declare(strict_types=1);

namespace App\Enums;

enum TransportType: string
{
    case COMPANY_VEHICLE = 'company_vehicle';
    case PERSONAL_VEHICLE = 'personal_vehicle';
    case PUBLIC_TRANSPORT = 'public_transport';
    case ON_FOOT = 'on_foot';

    public function getLabel(): string
    {
        return match ($this) {
            self::COMPANY_VEHICLE => 'Company Vehicle',
            self::PERSONAL_VEHICLE => 'Personal Vehicle',
            self::PUBLIC_TRANSPORT => 'Public Transport',
            self::ON_FOOT => 'On Foot',
        };
    }

    public function requiresVehicle(): bool
    {
        return in_array($this, [self::COMPANY_VEHICLE, self::PERSONAL_VEHICLE], true);
    }
}