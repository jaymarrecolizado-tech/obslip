<?php

declare(strict_types=1);

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use HasUuids;
    use Notifiable;
    use SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'avatar_path',
        'is_active',
        'department_id',
        'position',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }

    // --- Relationships ---

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function departmentHead(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'head_id');
    }

    public function createdPassSlips(): HasMany
    {
        return $this->hasMany(PassSlip::class, 'creator_id');
    }

    public function supervisedPassSlips(): HasMany
    {
        return $this->hasMany(PassSlip::class, 'supervisor_id');
    }

    public function approvedPassSlips(): HasMany
    {
        return $this->hasMany(PassSlip::class, 'approver_id');
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    // --- Helpers ---

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function getFullNameAttribute(): string
    {
        return $this->name;
    }
}
