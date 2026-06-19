<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CertificateStatus;
use App\Enums\CertificateType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Certificate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pass_slip_id',
        'type',
        'office_name',
        'representative_name',
        'representative_position',
        'representative_contact',
        'time_from',
        'time_to',
        'signature_path',
        'attachment_path',
        'status',
        'submitted_by',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'type' => CertificateType::class,
        'status' => CertificateStatus::class,
        'time_from' => 'datetime',
        'time_to' => 'datetime',
        'verified_at' => 'datetime',
    ];

    protected $appends = [
        'status_label',
        'status_color',
    ];

    public function passSlip(): BelongsTo
    {
        return $this->belongsTo(PassSlip::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status->getLabel();
    }

    public function getStatusColorAttribute(): string
    {
        return $this->status->getColor();
    }

    public function getSignatureUrlAttribute(): ?string
    {
        if (!$this->signature_path) {
            return null;
        }

        return asset('storage/' . $this->signature_path);
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        if (!$this->attachment_path) {
            return null;
        }

        return asset('storage/' . $this->attachment_path);
    }

    public function scopeByStatus($query, CertificateStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPassSlip($query, $passSlipId)
    {
        return $query->where('pass_slip_id', $passSlipId);
    }

    public function scopeSubmittedBy($query, $userId)
    {
        return $query->where('submitted_by', $userId);
    }

    public function scopeUnverified($query)
    {
        return $query->where('status', CertificateStatus::SUBMITTED);
    }

    public function markAsVerified(User $verifier): bool
    {
        if ($this->status !== CertificateStatus::SUBMITTED) {
            return false;
        }

        $this->status = CertificateStatus::VERIFIED;
        $this->verified_by = $verifier->id;
        $this->verified_at = now();
        $this->save();

        // Log audit
        AuditLog::create([
            'user_id' => $verifier->id,
            'auditable_type' => self::class,
            'auditable_id' => $this->id,
            'action' => 'verified',
            'old_values' => ['status' => CertificateStatus::SUBMITTED->value],
            'new_values' => ['status' => CertificateStatus::VERIFIED->value],
        ]);

        return true;
    }
}