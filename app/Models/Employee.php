<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EmploymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

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

    protected $casts = [
        'date_hired' => 'date',
        'employment_status' => EmploymentStatus::class,
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'full_name',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'owner_id');
    }

    public function passSlips(): HasMany
    {
        return $this->hasMany(PassSlip::class);
    }

    public function groupPassSlips(): BelongsToMany
    {
        return $this->belongsToMany(PassSlip::class, 'pass_slip_employee');
    }

    public function getFullNameAttribute(): string
    {
        $parts = array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
            $this->suffix,
        ]);

        return implode(' ', $parts);
    }

    public function getFullNameWithSuffixAttribute(): string
    {
        return trim($this->full_name . ' ' . $this->suffix);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeByStatus($query, EmploymentStatus $status)
    {
        return $query->where('employment_status', $status);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('employee_number', 'like', "%{$search}%")
                ->orWhere('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        });
    }
}