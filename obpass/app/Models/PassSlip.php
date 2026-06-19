<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PassSlipStatus;
use App\Enums\TransportType;
use App\Models\PassSlipEmployee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PassSlip extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'slip_number',
        'date',
        'purpose',
        'transport_type',
        'status',
        'creator_id',
        'supervisor_id',
        'approver_id',
        'department_id',
        'vehicle_id',
        'departure_time',
        'arrival_time',
        'approved_at',
        'completed_at',
        'cancelled_at',
        'returned_reason',
        'duration_hours',
        'is_emergency',
        'pdf_path',
        'qr_code',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'transport_type' => TransportType::class,
            'status' => PassSlipStatus::class,
            'departure_time' => 'datetime',
            'arrival_time' => 'datetime',
            'approved_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'duration_hours' => 'decimal:2',
            'is_emergency' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PassSlip $slip) {
            if (empty($slip->slip_number)) {
                $slip->slip_number = self::generateSlipNumber();
            }
            if (empty($slip->qr_code)) {
                $slip->qr_code = (string) Str::uuid();
            }
        });
    }

    // --- Relationships ---

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'pass_slip_employee')
            ->using(PassSlipEmployee::class)
            ->withTimestamps();
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function certificate(): HasOne
    {
        return $this->hasOne(Certificate::class);
    }

    // --- State Transitions ---

    public function submit(): bool
    {
        if ($this->status !== PassSlipStatus::Draft) {
            return false;
        }
        return $this->update(['status' => PassSlipStatus::Submitted]);
    }

    public function approve(User $approver): bool
    {
        if ($this->status !== PassSlipStatus::Submitted) {
            return false;
        }
        return $this->update([
            'status' => PassSlipStatus::Approved,
            'approver_id' => $approver->id,
            'approved_at' => now(),
        ]);
    }

    public function returnWithReason(User $supervisor, string $reason): bool
    {
        if ($this->status !== PassSlipStatus::Submitted) {
            return false;
        }
        return $this->update([
            'status' => PassSlipStatus::Returned,
            'returned_reason' => $reason,
            'supervisor_id' => $supervisor->id,
        ]);
    }

    public function depart(): bool
    {
        if ($this->status !== PassSlipStatus::Approved) {
            return false;
        }
        return $this->update([
            'status' => PassSlipStatus::Departed,
            'departure_time' => now(),
        ]);
    }

    public function arrive(): bool
    {
        if ($this->status !== PassSlipStatus::Departed) {
            return false;
        }

        $updates = [
            'status' => PassSlipStatus::Arrived,
            'arrival_time' => now(),
        ];

        if ($this->departure_time) {
            $duration = $this->departure_time->diffInMinutes(now()) / 60;
            $updates['duration_hours'] = round($duration, 2);
        }

        return $this->update($updates);
    }

    public function submitCertificate(): bool
    {
        if ($this->status !== PassSlipStatus::Arrived) {
            return false;
        }
        return $this->update(['status' => PassSlipStatus::CertificateSubmitted]);
    }

    public function verify(): bool
    {
        if ($this->status !== PassSlipStatus::CertificateSubmitted) {
            return false;
        }
        return $this->update(['status' => PassSlipStatus::Verified]);
    }

    public function complete(): bool
    {
        if ($this->status !== PassSlipStatus::Verified) {
            return false;
        }
        return $this->update([
            'status' => PassSlipStatus::Completed,
            'completed_at' => now(),
        ]);
    }

    public function cancel(): bool
    {
        if (!$this->status->canBeCancelled()) {
            return false;
        }
        return $this->update([
            'status' => PassSlipStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }

    // --- Scopes ---

    public function scopeForDepartment($query, string $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeForCreator($query, string $userId)
    {
        return $query->where('creator_id', $userId);
    }

    public function scopeForSupervisor($query, string $userId)
    {
        return $query->where('supervisor_id', $userId);
    }

    public function scopeForEmployee($query, string $employeeId)
    {
        return $query->whereHas('employees', function ($q) use ($employeeId) {
            $q->where('employees.id', $employeeId);
        });
    }

    public function scopeStatus($query, PassSlipStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [PassSlipStatus::Cancelled, PassSlipStatus::Completed]);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('date', today());
    }

    // --- Helpers ---

    public static function generateSlipNumber(): string
    {
        $year = date('Y');
        $sequence = static::whereYear('created_at', $year)->count() + 1;
        return sprintf('OB-%s-%04d', $year, $sequence);
    }

    public function getQrVerificationUrlAttribute(): string
    {
        return url("/verify/{$this->qr_code}");
    }
}
