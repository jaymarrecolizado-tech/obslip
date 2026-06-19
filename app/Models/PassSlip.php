<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PassSlipStatus;
use App\Enums\TransportType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class PassSlip extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'slip_number',
        'date',
        'purpose',
        'transport_type',
        'status',
        'creator_id',
        'supervisor_id',
        'approver_id',
        'employee_id',
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

    protected $casts = [
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

    protected $appends = [
        'status_label',
        'status_color',
    ];

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

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function groupEmployees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'pass_slip_employee');
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status->getLabel();
    }

    public function getStatusColorAttribute(): string
    {
        return $this->status->getColor();
    }

    public function getPdfUrlAttribute(): ?string
    {
        if (!$this->pdf_path) {
            return null;
        }

        return asset('storage/' . $this->pdf_path);
    }

    public function getQrCodeUrlAttribute(): ?string
    {
        if (!$this->qr_code) {
            return null;
        }

        return route('verify.qr', ['qr_code' => $this->qr_code]);
    }

    public function getDurationAttribute(): ?string
    {
        if (!$this->departure_time || !$this->arrival_time) {
            return null;
        }

        $minutes = $this->departure_time->diffInMinutes($this->arrival_time);
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        if ($hours > 0) {
            return "{$hours}h {$mins}m";
        }

        return "{$mins}m";
    }

    public function scopeByStatus(Builder $query, PassSlipStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeByDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('date', $date);
    }

    public function scopeByDateRange(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('date', [$from, $to]);
    }

    public function scopeByDepartment(Builder $query, $departmentId): Builder
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeByEmployee(Builder $query, $employeeId): Builder
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('date', today());
    }

    public function scopeForGuard(Builder $query): Builder
    {
        return $query->whereIn('status', [
            PassSlipStatus::APPROVED,
            PassSlipStatus::DEPARTED,
        ]);
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('slip_number', 'like', "%{$search}%")
                ->orWhereHas('employee', function ($employeeQuery) use ($search) {
                    $employeeQuery->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('employee_number', 'like', "%{$search}%");
                });
        });
    }

    public function transitionTo(PassSlipStatus $newStatus, ?User $actor = null, ?string $reason = null): bool
    {
        if (!$this->status->canTransitionTo($newStatus)) {
            return false;
        }

        $oldStatus = $this->status;
        $this->status = $newStatus;

        // Set timestamps based on status
        match ($newStatus) {
            PassSlipStatus::APPROVED => $this->approved_at = now(),
            PassSlipStatus::DEPARTED => $this->departure_time = now(),
            PassSlipStatus::ARRIVED => $this->arrival_time = now(),
            PassSlipStatus::COMPLETED => $this->completed_at = now(),
            PassSlipStatus::CANCELLED => $this->cancelled_at = now(),
            default => null,
        };

        // Set returned reason if applicable
        if ($newStatus === PassSlipStatus::RETURNED && $reason) {
            $this->returned_reason = $reason;
        }

        // Calculate duration when arrived
        if ($newStatus === PassSlipStatus::ARRIVED && $this->departure_time) {
            $this->duration_hours = $this->departure_time
                ->diffInMinutes(now()) / 60;
        }

        $this->save();

        // Log audit trail
        AuditLog::create([
            'user_id' => $actor?->id,
            'auditable_type' => self::class,
            'auditable_id' => $this->id,
            'action' => 'state_transition',
            'old_values' => ['status' => $oldStatus->value],
            'new_values' => [
                'status' => $newStatus->value,
                'reason' => $reason,
            ],
        ]);

        return true;
    }

    public function canBeCancelledBy(User $user): bool
    {
        // Draft or submitted slips can be cancelled by creator
        if (in_array($this->status, [PassSlipStatus::DRAFT, PassSlipStatus::SUBMITTED], true)) {
            return $this->creator_id === $user->id;
        }

        // Approved slips can be cancelled by admin or supervisor
        if ($this->status === PassSlipStatus::APPROVED) {
            return $user->hasAnyRole(['admin', 'supervisor']);
        }

        return false;
    }

    public function canBeApprovedBy(User $user): bool
    {
        if ($this->status !== PassSlipStatus::SUBMITTED) {
            return false;
        }

        // Supervisors can approve slips from their department
        if ($user->hasRole('supervisor')) {
            return $this->department_id === $user->department_id;
        }

        return false;
    }

    public function canBeReturnedBy(User $user): bool
    {
        if ($this->status !== PassSlipStatus::SUBMITTED) {
            return false;
        }

        // Supervisors can return slips from their department
        if ($user->hasRole('supervisor')) {
            return $this->department_id === $user->department_id;
        }

        return false;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($passSlip) {
            if (empty($passSlip->slip_number)) {
                $year = now()->format('Y');
                $lastSlip = static::whereYear('date', $year)
                    ->orderBy('slip_number', 'desc')
                    ->first();

                $sequence = $lastSlip
                    ? (int) str_replace("OB-{$year}-", '', $lastSlip->slip_number) + 1
                    : 1;

                $passSlip->slip_number = sprintf("OB-%s-%06d", $year, $sequence);
            }

            if (empty($passSlip->qr_code)) {
                $passSlip->qr_code = (string) Str::uuid();
            }
        });
    }
}