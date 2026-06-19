<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PassSlipStatus;
use App\Enums\TransportType;
use App\Models\PassSlip;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PassSlipService
{
    public function create(array $data, string $creatorId): PassSlip
    {
        return DB::transaction(function () use ($data, $creatorId) {
            $employee = Employee::findOrFail($data['employee_id']);

            $passSlip = PassSlipcreate([
                'date' => $data['date'],
                'purpose' => $data['purpose'],
                'transport_type' => $data['transport_type'],
                'status' => PassSlipStatus::DRAFT,
                'creator_id' => $creatorId,
                'employee_id' => $employee->id,
                'department_id' => $employee->department_id,
                'vehicle_id' => $data['vehicle_id'] ?? null,
                'is_emergency' => $data['is_emergency'] ?? false,
                'qr_code' => (string) \Illuminate\Support\Str::uuid(),
            ]);

            // Set supervisor as department head
            if ($employee->department?->head_id) {
                $passSlip->supervisor_id = $employee->department->head_id;
            }

            // Add additional employees for group travel
            if (isset($data['additional_employees']) && is_array($data['additional_employees'])) {
                $passSlip->groupEmployees()->sync($data['additional_employees']);
            }

            // Log creation
            $passSlip->auditLogs()->create([
                'user_id' => $creatorId,
                'action' => 'created',
                'new_values' => $data,
            ]);

            Log::info('Pass slip created', [
                'pass_slip_id' => $passSlip->id,
                'creator_id' => $creatorId,
                'employee_id' => $passSlip->employee_id,
            ]);

            return $passSlip;
        });
    }

    public function update(PassSlip $passSlip, array $data): PassSlip
    {
        return DB::transaction(function () use ($passSlip, $data) {
            $oldValues = $passSlip->only([
                'date', 'purpose', 'transport_type', 'employee_id',
                'department_id', 'vehicle_id', 'is_emergency',
            ]);

            $passSlip->update($data);

            // Update department if employee changed
            if (isset($data['employee_id'])) {
                $employee = Employee::findOrFail($data['employee_id']);
                $passSlip->department_id = $employee->department_id;
            }

            // Update supervisor if department changed
            if ($passSlip->department?->head_id) {
                $passSlip->supervisor_id = $passSlip->department->head_id;
            }

            // Sync group employees
            if (isset($data['additional_employees'])) {
                $passSlip->groupEmployees()->sync($data['additional_employees']);
            }

            // Log update
            $passSlip->auditLogs()->create([
                'user_id' => Auth::id(),
                'action' => 'updated',
                'old_values' => $oldValues,
                'new_values' => $data,
            ]);

            Log::info('Pass slip updated', [
                'pass_slip_id' => $passSlip->id,
                'updater_id' => Auth::id(),
            ]);

            return $passSlip;
        });
    }

    public function getPendingCount(): int
    {
        return PassSlip::where('status', PassSlipStatus::SUBMITTED)
            ->count();
    }

    public function getTodayCount(): int
    {
        return PassSlip::today()
            ->whereIn('status', [
                PassSlipStatus::APPROVED,
                PassSlipStatus::DEPARTED,
                PassSlipStatus::ARRIVED,
            ])
            ->count();
    }

    public function getByStatus(PassSlipStatus $status, ?User $user = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = PassSlip::where('status', $status);

        if ($user) {
            if ($user->hasRole('supervisor')) {
                $query->byDepartment($user->department_id);
            } else {
                $query->where('creator_id', $user->id);
            }
        }

        return $query->with(['employee', 'department', 'creator'])->get();
    }

    public function getForDepartment(string $departmentId, \Carbon\Carbon $from, \Carbon\Carbon $to): \Illuminate\Database\Eloquent\Collection
    {
        return PassSlip::byDepartment($departmentId)
            ->whereBetween('date', [$from->format('Y-m-d'), $to->format('Y-m-d')])
            ->with(['employee', 'department', 'creator'])
            ->orderBy('date', 'desc')
            ->get();
    }
}