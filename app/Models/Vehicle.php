<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'plate_number',
        'make',
        'model',
        'year',
        'color',
        'owner_id',
        'is_active',
    ];

    protected $casts = [
        'year' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'full_name',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'owner_id');
    }

    public function passSlips(): HasMany
    {
        return $this->hasMany(PassSlip::class);
    }

    public function getFullNameAttribute(): string
    {
        $parts = array_filter([
            $this->year ? (string) $this->year : null,
            $this->make,
            $this->model,
            $this->color,
        ]);

        return implode(' ', $parts);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCompanyVehicles($query)
    {
        return $query->whereNull('owner_id');
    }

    public function scopePersonalVehicles($query)
    {
        return $query->whereNotNull('owner_id');
    }

    public function scopeByOwner($query, $employeeId)
    {
        return $query->where('owner_id', $employeeId);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('plate_number', 'like', "%{$search}%")
                ->orWhere('make', 'like', "%{$search}%")
                ->orWhere('model', 'like', "%{$search}%");
        });
    }
}