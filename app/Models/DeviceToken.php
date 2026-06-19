<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DevicePlatform;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token',
        'platform',
        'last_used_at',
    ];

    protected $casts = [
        'platform' => DevicePlatform::class,
        'last_used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByPlatform($query, DevicePlatform $platform)
    {
        return $query->where('platform', $platform);
    }

    public function markAsUsed(): void
    {
        $this->last_used_at = now();
        $this->save();
    }
}