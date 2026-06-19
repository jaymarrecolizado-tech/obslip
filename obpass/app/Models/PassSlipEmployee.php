<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Str;

class PassSlipEmployee extends Pivot
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function booted(): void
    {
        static::creating(function (PassSlipEmployee $pivot) {
            if (empty($pivot->id)) {
                $pivot->id = (string) Str::uuid();
            }
        });
    }
}
