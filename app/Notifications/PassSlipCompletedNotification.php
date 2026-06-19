<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PassSlipCompletedNotification extends Notification
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
            ->subject('Pass Slip Completed: ' . $this->passSlip->slip_number)
            ->greeting('Dear ' . $notifiable->name . ',)
            ->line('Your pass slip has been marked as complete.')
            ->line('')
            ->line('Pass Slip Number: ' . $this->passSlip->slipNumber)
            ->line('Date: ' . $this->passSlip->date->format('F j, Y'))
            ->line('Purpose: ' . $this->passSlip->purpose)
            ->line('Duration: ' . ($this->passSlip->duration ?? 'N/A'))
            ->line('')
            ->line('All official business requirements have been fulfilled.')
            ->line('Thank you for using the system.')
            ->salutation('Best regards,')
            ->subject('✅ Completed: ' . $this->passSlip->slip_number);
    }

    public function toDatabase(User $notifiable): array
    {
        return [
            'notifiable_type' => User::class,
            'notifiable_id' => $notifiable->id,
            'data' => [
                'pass_slip_id' => $this->passSlip->id,
                'slip_number' => $this->passSlip->slip_number,
                'status' => 'completed',
                'duration' => $this->passSlip->duration_hours,
            ],
        ];
    }

    public function toArray(User $notifiable): array
    {
        return [
            'title' => 'Pass Slip Completed',
            'body' => "Your pass slip ({$this->passSlip->slip_number}) has been completed successfully.",
            'type' => 'pass_slip_completed',
        ];
    }
}