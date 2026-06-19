<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CertificateStatus;
use App\Enums\CertificateType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Certificate extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

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

    protected function casts(): array
    {
        return [
            'type' => CertificateType::class,
            'status' => CertificateStatus::class,
            'time_from' => 'datetime:H:i',
            'time_to' => 'datetime:H:i',
            'verified_at' => 'datetime',
        ];
    }

    // --- Relationships ---

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

    // --- State Transitions ---

    public function submit(User $submittedBy): bool
    {
        if ($this->status !== CertificateStatus::Draft) {
            return false;
        }
        return $this->update([
            'status' => CertificateStatus::Submitted,
            'submitted_by' => $submittedBy->id,
        ]);
    }

    public function verify(User $verifiedBy): bool
    {
        if ($this->status !== CertificateStatus::Submitted) {
            return false;
        }
        return $this->update([
            'status' => CertificateStatus::Verified,
            'verified_by' => $verifiedBy->id,
            'verified_at' => now(),
        ]);
    }
}
