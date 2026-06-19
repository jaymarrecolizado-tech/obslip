<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\Certificate;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PassSlip;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vehicle;
use App\Policies\AuditLogPolicy;
use App\Policies\CertificatePolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\PassSlipPolicy;
use App\Policies\SettingPolicy;
use App\Policies\UserPolicy;
use App\Policies\VehiclePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => UserPolicy::class,
        Department::class => DepartmentPolicy::class,
        Employee::class => EmployeePolicy::class,
        Vehicle::class => VehiclePolicy::class,
        PassSlip::class => PassSlipPolicy::class,
        Certificate::class => CertificatePolicy::class,
        AuditLog::class => AuditLogPolicy::class,
        Setting::class => SettingPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}