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

        static::created(function (PassSlip $slip) {
            AuditLog::log('created', $slip, null, [
                'slip_number' => $slip->slip_number,
                'status' => $slip->status->value,
            ]);
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
    // Every transition is recorded in the audit log (spec: "All transitions are audit logged").

    public function submit(): bool
    {
        // Draft slips can be submitted; Returned slips can be resubmitted (spec alternate path).
        if (! in_array($this->status, [PassSlipStatus::Draft, PassSlipStatus::Returned], true)) {
            return false;
        }

        $old = ['status' => $this->status->value];
        $updated = $this->update([
            'status' => PassSlipStatus::Submitted,
            'returned_reason' => null,
        ]);

        if ($updated) {
            $this->logTransition($old, ['status' => PassSlipStatus::Submitted->value]);
        }

        return $updated;
    }

    public function approve(User $approver): bool
    {
        if ($this->status !== PassSlipStatus::Submitted) {
            return false;
        }

        $old = ['status' => $this->status->value];
        $updated = $this->update([
            'status' => PassSlipStatus::Approved,
            'approver_id' => $approver->id,
            'approved_at' => now(),
        ]);

        if ($updated) {
            $this->logTransition($old, [
                'status' => PassSlipStatus::Approved->value,
                'approver_id' => $approver->id,
            ]);
        }

        return $updated;
    }

    public function returnWithReason(User $supervisor, string $reason): bool
    {
        if ($this->status !== PassSlipStatus::Submitted) {
            return false;
        }

        $old = ['status' => $this->status->value];
        $updated = $this->update([
            'status' => PassSlipStatus::Returned,
            'returned_reason' => $reason,
            'supervisor_id' => $supervisor->id,
        ]);

        if ($updated) {
            $this->logTransition($old, [
                'status' => PassSlipStatus::Returned->value,
                'returned_reason' => $reason,
            ]);
        }

        return $updated;
    }

    public function depart(): bool
    {
        if ($this->status !== PassSlipStatus::Approved) {
            return false;
        }

        $old = ['status' => $this->status->value];
        $updated = $this->update([
            'status' => PassSlipStatus::Departed,
            'departure_time' => now(),
        ]);

        if ($updated) {
            $this->logTransition($old, ['status' => PassSlipStatus::Departed->value]);
        }

        return $updated;
    }

    public function arrive(): bool
    {
        if ($this->status !== PassSlipStatus::Departed) {
            return false;
        }

        $old = ['status' => $this->status->value];

        $updates = [
            'status' => PassSlipStatus::Arrived,
            'arrival_time' => now(),
        ];

        $new = ['status' => PassSlipStatus::Arrived->value];

        if ($this->departure_time) {
            $duration = $this->departure_time->diffInMinutes(now()) / 60;
            $updates['duration_hours'] = round($duration, 2);
            $new['duration_hours'] = $updates['duration_hours'];
        }

        $updated = $this->update($updates);

        if ($updated) {
            $this->logTransition($old, $new);
        }

        return $updated;
    }

    public function submitCertificate(): bool
    {
        if ($this->status !== PassSlipStatus::Arrived) {
            return false;
        }

        $old = ['status' => $this->status->value];
        $updated = $this->update(['status' => PassSlipStatus::CertificateSubmitted]);

        if ($updated) {
            $this->logTransition($old, ['status' => PassSlipStatus::CertificateSubmitted->value]);
        }

        return $updated;
    }

    public function verify(): bool
    {
        if ($this->status !== PassSlipStatus::CertificateSubmitted) {
            return false;
        }

        $old = ['status' => $this->status->value];
        $updated = $this->update(['status' => PassSlipStatus::Verified]);

        if ($updated) {
            $this->logTransition($old, ['status' => PassSlipStatus::Verified->value]);
        }

        return $updated;
    }

    public function complete(): bool
    {
        if ($this->status !== PassSlipStatus::Verified) {
            return false;
        }

        $old = ['status' => $this->status->value];
        $updated = $this->update([
            'status' => PassSlipStatus::Completed,
            'completed_at' => now(),
        ]);

        if ($updated) {
            $this->logTransition($old, ['status' => PassSlipStatus::Completed->value]);
        }

        return $updated;
    }

    public function cancel(?User $actor = null): bool
    {
        if (! in_array($this->status, $this->cancellableStatusesFor($actor), true)) {
            return false;
        }

        $old = ['status' => $this->status->value];
        $updated = $this->update([
            'status' => PassSlipStatus::Cancelled,
            'cancelled_at' => now(),
        ]);

        if ($updated) {
            $this->logTransition($old, ['status' => PassSlipStatus::Cancelled->value]);
        }

        return $updated;
    }

    /**
     * Statuses that may be cancelled, scoped to the acting user's role.
     * Employees may only cancel Draft/Submitted slips they own; Admins and
     * Supervisors may additionally cancel Approved slips (spec alternate path).
     */
    protected function cancellableStatusesFor(?User $user): array
    {
        $statuses = [PassSlipStatus::Draft, PassSlipStatus::Submitted];

        if ($user && ($user->hasRole('Admin') || $user->hasRole('Supervisor'))) {
            $statuses[] = PassSlipStatus::Approved;
        }

        return $statuses;
    }

    /**
     * Record a state transition in the audit log.
     */
    protected function logTransition(array $old, array $new): void
    {
        AuditLog::log('state_transition', $this, $old, $new);
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
