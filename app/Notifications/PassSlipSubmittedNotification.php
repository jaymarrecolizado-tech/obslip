<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PassSlipSubmittedNotification extends Notification
{
    public function __construct(
        public PassSlip $passSlip
    ) {}

    public function via(User $notifiable): array
    {
        return ['mail', 'database', 'fcm'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Pass Slip Submitted: ' . $this->passSlip->slip_number)
            ->greeting('Dear ' . $notifiable->name . ',)
            ->line('Your pass slip has been submitted for approval.')
            ->line('')
            ->line('Pass Slip Number: ' . $this->passSlip->slip_number)
            ->line('Date: ' . $this->passSlip->date->format('F j, Y'))
            ->line('Purpose: ' . $this->passSlip->purpose)
            ->line('Status: Pending Supervisor Approval')
            ->line('Department: ' . $this->passSlip->department->name)
            ->line('')
            ->action('You will be notified when it is approved or returned.')
            ->salutation('Best regards,')
            ->subject('📋 ' . $this->passSlip->slip_number);
    }

    public function toDatabase(User $notifiable): array
    {
        return [
            'notifiable_type' => User::class,
            'notifiable_id' => $notifiable->id,
            'data' => [
                'pass_slip_id' => $this->passSlip->id,
                'slip_number' => $this->passSlip->slip_number,
                'employee_name' => $this->passSlip->employee->full_name,
                'status' => 'submitted',
                'action_url' => route('pass-slips.show', $this->passSlip->id),
            ],
        ];
    }

    public function toArray(User $notifiable): array
    {
        return [
            'title' => 'Pass Slip Submitted',
            'body' => "Your pass slip ({$this->passSlip->slip_number}) has been submitted for approval.",
            'type' => 'pass_slip_submitted',
        ];
    }
}