<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasApiTokens, Notifiable, SoftDeletes, HasRoles;

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

    protected $casts = [
        'is_active' => 'boolean',
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function headOfDepartments(): HasMany
    {
        return $this->hasMany(Department::class, 'head_id');
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

    public function submittedCertificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'submitted_by');
    }

    public function verifiedCertificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'verified_by');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function notifications(): MorphToMany
    {
        return $this->morphToMany(
            \Illuminate\Notifications\DatabaseNotification::class,
            'notifiable',
            'notifications',
            'notifiable_id',
            'id'
        )->orderBy('created_at', 'desc');
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function getFullNameAttribute(): string
    {
        return $this->name;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (!$this->avatar_path) {
            return null;
        }

        return asset('storage/' . $this->avatar_path);
    }
}