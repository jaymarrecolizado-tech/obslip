<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PassSlipApprovedNotification extends Notification
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
            ->subject('Pass Slip Approved: ' . $this->passSlip->slip_number)
            ->greeting('Dear ' . $notifiable->name . ',)
            ->line('Good news! Your pass slip has been approved.')
            ->line('')
            ->line('Pass Slip Number: ' . $this->passSlip->slip_number)
            ->line('Date: ' . $this->passSlip->date->format('F j, Y'))
            ->line('Purpose: ' . $this->passSlip->purpose)
            ->line('Approved by: ' . $this->passSlip->approver->name)
            ->line('Transport: ' . $this->passSlip->transport_type->getLabel())
            ->line('')
            ->line('You can now depart for your official business.')
            ->action('View slip: ' . route('pass-slips.show', $this->passSlip->id))
            ->line('Your PDF is available in the system.')
            ->salutation('Safe travels,')
            ->subject('✅ ' . $this->passSlip->slip_number . ' - Approved');
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
                'status' => 'approved',
                'action_url' => route('pass-slips.show', $this->passSlip->id),
            ],
        ];
    }

    public function toArray(User $notifiable): array
    {
        return [
            'title' => 'Pass Slip Approved',
            'body' => "Your pass slip ({$this->passSlip->slip_number}) has been approved and you can now depart.",
            'type' => 'pass_slip_approved',
        ];
    }
}