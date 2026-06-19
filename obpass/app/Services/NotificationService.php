<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Employee;
use App\Models\Notification;
use App\Models\PassSlip;
use App\Models\Setting;
use App\Models\User;

class NotificationService
{
    /**
     * Notify supervisor when a pass slip is submitted.
     */
    public function notifySupervisorOnSubmit(PassSlip $passSlip): void
    {
        if (! Setting::getValue('notify_supervisor_on_submit', true)) {
            return;
        }

        $supervisor = $passSlip->supervisor;
        if (! $supervisor) {
            return;
        }

        $employeeNames = $passSlip->employees->pluck('full_name')->implode(', ');

        $this->create(
            type: 'status_change',
            notifiable: $supervisor,
            data: [
                'action' => 'submitted',
                'pass_slip_id' => $passSlip->id,
                'slip_number' => $passSlip->slip_number,
                'employee_names' => $employeeNames,
                'message' => "Pass slip {$passSlip->slip_number} submitted by {$employeeNames} for your approval.",
            ]
        );
    }

    /**
     * Notify the employee(s)/creator that a pass slip was submitted (confirmation).
     * Spec: SlipSubmitted → Supervisor + Employee.
     */
    public function notifyEmployeeOnSubmit(PassSlip $passSlip): void
    {
        if (! Setting::getValue('notify_employee_on_submit', true)) {
            return;
        }

        $notifiables = collect([$passSlip->creator]);

        foreach ($passSlip->employees as $employee) {
            if ($employee->user && $employee->user->id !== $passSlip->creator_id) {
                $notifiables->push($employee->user);
            }
        }

        foreach ($notifiables->filter() as $user) {
            $this->create(
                type: 'status_change',
                notifiable: $user,
                data: [
                    'action' => 'submitted',
                    'pass_slip_id' => $passSlip->id,
                    'slip_number' => $passSlip->slip_number,
                    'message' => "Your pass slip {$passSlip->slip_number} has been submitted for approval.",
                ]
            );
        }
    }

    /**
     * Notify employee(s) when a pass slip is approved.
     */
    public function notifyEmployeeOnApprove(PassSlip $passSlip): void
    {
        if (! Setting::getValue('notify_employee_on_approve', true)) {
            return;
        }

        foreach ($passSlip->employees as $employee) {
            $user = $employee->user;
            if (! $user) {
                continue;
            }

            $this->create(
                type: 'status_change',
                notifiable: $user,
                data: [
                    'action' => 'approved',
                    'pass_slip_id' => $passSlip->id,
                    'slip_number' => $passSlip->slip_number,
                    'message' => "Your pass slip {$passSlip->slip_number} has been approved.",
                ]
            );
        }
    }

    /**
     * Notify guards when a pass slip is approved (so they expect the departure).
     * Spec: SlipApproved → Employee + Guard.
     */
    public function notifyGuardsOnApprove(PassSlip $passSlip): void
    {
        $guards = User::where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->where('name', 'Guard'))
            ->get();

        foreach ($guards as $guard) {
            $this->create(
                type: 'status_change',
                notifiable: $guard,
                data: [
                    'action' => 'approved',
                    'pass_slip_id' => $passSlip->id,
                    'slip_number' => $passSlip->slip_number,
                    'message' => "Pass slip {$passSlip->slip_number} has been approved and is ready for departure.",
                ]
            );
        }
    }

    /**
     * Notify employee(s) when a pass slip is returned.
     */
    public function notifyEmployeeOnReturn(PassSlip $passSlip): void
    {
        if (! Setting::getValue('notify_employee_on_return', true)) {
            return;
        }

        foreach ($passSlip->employees as $employee) {
            $user = $employee->user;
            if (! $user) {
                continue;
            }

            $this->create(
                type: 'status_change',
                notifiable: $user,
                data: [
                    'action' => 'returned',
                    'pass_slip_id' => $passSlip->id,
                    'slip_number' => $passSlip->slip_number,
                    'reason' => $passSlip->returned_reason,
                    'message' => "Your pass slip {$passSlip->slip_number} has been returned. Reason: {$passSlip->returned_reason}",
                ]
            );
        }
    }

    /**
     * Notify supervisor when an employee departs.
     */
    public function notifySupervisorOnDepart(PassSlip $passSlip): void
    {
        if (! Setting::getValue('notify_supervisor_on_depart', true)) {
            return;
        }

        $supervisor = $passSlip->supervisor;
        if (! $supervisor) {
            return;
        }

        $employeeNames = $passSlip->employees->pluck('full_name')->implode(', ');

        $this->create(
            type: 'status_change',
            notifiable: $supervisor,
            data: [
                'action' => 'departed',
                'pass_slip_id' => $passSlip->id,
                'slip_number' => $passSlip->slip_number,
                'employee_names' => $employeeNames,
                'departure_time' => $passSlip->departure_time?->toIso8601String(),
                'message' => "{$employeeNames} departed for pass slip {$passSlip->slip_number}.",
            ]
        );
    }

    /**
     * Notify supervisor when an employee arrives.
     */
    public function notifySupervisorOnArrive(PassSlip $passSlip): void
    {
        if (! Setting::getValue('notify_supervisor_on_arrive', true)) {
            return;
        }

        $supervisor = $passSlip->supervisor;
        if (! $supervisor) {
            return;
        }

        $employeeNames = $passSlip->employees->pluck('full_name')->implode(', ');

        $this->create(
            type: 'status_change',
            notifiable: $supervisor,
            data: [
                'action' => 'arrived',
                'pass_slip_id' => $passSlip->id,
                'slip_number' => $passSlip->slip_number,
                'employee_names' => $employeeNames,
                'arrival_time' => $passSlip->arrival_time?->toIso8601String(),
                'duration_hours' => $passSlip->duration_hours,
                'message' => "{$employeeNames} arrived for pass slip {$passSlip->slip_number}. Duration: {$passSlip->duration_hours} hours.",
            ]
        );
    }

    /**
     * Notify HR and Admin when a certificate is submitted.
     * Spec: CertificateSubmitted → HR + Admin.
     */
    public function notifyHrOnCertificate(PassSlip $passSlip): void
    {
        if (! Setting::getValue('notify_hr_on_certificate', true)) {
            return;
        }

        $hrUsers = User::where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['HR', 'Admin']))
            ->get();

        $employeeNames = $passSlip->employees->pluck('full_name')->implode(', ');

        foreach ($hrUsers as $hr) {
            $this->create(
                type: 'certificate_submitted',
                notifiable: $hr,
                data: [
                    'action' => 'certificate_submitted',
                    'pass_slip_id' => $passSlip->id,
                    'slip_number' => $passSlip->slip_number,
                    'employee_names' => $employeeNames,
                    'message' => "Certificate submitted for pass slip {$passSlip->slip_number} by {$employeeNames}.",
                ]
            );
        }
    }

    /**
     * Notify employee(s) when a certificate is verified.
     */
    public function notifyEmployeeOnVerify(PassSlip $passSlip): void
    {
        if (! Setting::getValue('notify_employee_on_verify', true)) {
            return;
        }

        foreach ($passSlip->employees as $employee) {
            $user = $employee->user;
            if (! $user) {
                continue;
            }

            $this->create(
                type: 'status_change',
                notifiable: $user,
                data: [
                    'action' => 'verified',
                    'pass_slip_id' => $passSlip->id,
                    'slip_number' => $passSlip->slip_number,
                    'message' => "Certificate for pass slip {$passSlip->slip_number} has been verified.",
                ]
            );
        }
    }

    /**
     * Notify employee(s) when a pass slip is completed.
     */
    public function notifyEmployeeOnComplete(PassSlip $passSlip): void
    {
        if (! Setting::getValue('notify_employee_on_complete', true)) {
            return;
        }

        foreach ($passSlip->employees as $employee) {
            $user = $employee->user;
            if (! $user) {
                continue;
            }

            $this->create(
                type: 'status_change',
                notifiable: $user,
                data: [
                    'action' => 'completed',
                    'pass_slip_id' => $passSlip->id,
                    'slip_number' => $passSlip->slip_number,
                    'message' => "Pass slip {$passSlip->slip_number} has been completed.",
                ]
            );
        }
    }

    /**
     * Create a notification record.
     */
    public function create(string $type, User $notifiable, array $data): Notification
    {
        return Notification::create([
            'type' => $type,
            'notifiable_type' => User::class,
            'notifiable_id' => $notifiable->id,
            'data' => $data,
        ]);
    }
}
