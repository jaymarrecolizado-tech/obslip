<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EmploymentStatus;
use App\Models\PassSlipEmployee;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'employee_number',
        'first_name',
        'last_name',
        'middle_name',
        'suffix',
        'email',
        'phone',
        'department_id',
        'position',
        'date_hired',
        'employment_status',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'date_hired' => 'date',
            'employment_status' => EmploymentStatus::class,
            'is_active' => 'boolean',
        ];
    }

    // --- Relationships ---

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function ownedVehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'owner_id');
    }

    public function passSlips(): BelongsToMany
    {
        return $this->belongsToMany(PassSlip::class, 'pass_slip_employee')
            ->using(PassSlipEmployee::class)
            ->withTimestamps();
    }

    // --- Accessors ---

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => collect([$this->first_name, $this->middle_name, $this->last_name, $this->suffix])
                ->filter()
                ->implode(' '),
        );
    }

    protected function initials(): Attribute
    {
        return Attribute::make(
            get: fn () => strtoupper(substr($this->first_name, 0, 1) . substr($this->last_name, 0, 1)),
        );
    }
}
