<?php

declare(strict_types=1);

namespace App\Enums;

enum TransportType: string
{
    case CompanyVehicle = 'company_vehicle';
    case PersonalVehicle = 'personal_vehicle';
    case PublicTransport = 'public_transport';
    case OnFoot = 'on_foot';

    public function label(): string
    {
        return match ($this) {
            self::CompanyVehicle => 'Company Vehicle',
            self::PersonalVehicle => 'Personal Vehicle',
            self::PublicTransport => 'Public Transport',
            self::OnFoot => 'On Foot',
        };
    }
}
