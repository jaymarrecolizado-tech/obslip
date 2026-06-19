<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PassSlipDepartedNotification extends Notification
{
    public function __construct(
        public PassSlip $passSlip
    ) {}

    public function via(User $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Employee Departed: ' . $this->passSlip->slip_number)
            ->greeting('Dear ' . $notifiable->name . ',)
            ->line('Your employee has departed for official business.')
            ->line('')
            ->line('Employee: ' . $this->passSlip->employee->full_name)
            ->line('Pass Slip: ' . $this->passSlip->slip_number)
            ->line('Purpose: ' . $this->passSlip->purpose)
            ->line('Departure Time: ' . $this->passSlip->departure_time->format('h:i A'))
            ->line('')
            ->action('View slip: ' . route('pass-slips.show', $this->passSlip->id))
            ->salutation('Regards,')
            ->subject('🚫 Departed: ' . $this->passSlip->slip_number);
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
                'status' => 'departed',
            ],
        ];
    }

    public function toArray(User $notifiable): array
    {
        return [
            'title' => 'Employee Departed',
            'body' => "{$this->passSlip->employee->full_name} has departed for {$this->passSlip->purpose}.",
            'type' => 'pass_slip_departed',
        ];
    }
}