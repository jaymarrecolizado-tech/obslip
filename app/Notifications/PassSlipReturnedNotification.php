<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PassSlipReturnedNotification extends Notification
{
    public function __construct(
        public PassSlip $passSlip,
        public string $reason
    ) {}

    public function via(User $notifiable): array
    {
        return ['mail', 'database', 'fcm'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Pass Slip Returned: ' . $this->passSlip->slip_number)
            ->greeting('Dear ' . $notifiable->name . ',)
            ->line('Your pass slip has been returned by the supervisor.')
            ->line('')
            ->line('Pass Slip Number: ' . $this->passSlip->slip_number)
            ->line('Date: ' . $this->passSlip->date->format('F j, Y'))
            ->line('Reason: ' . $this->reason)
            ->line('')
            ->line('Please address the feedback and resubmit if needed.')
            ->action('View slip: ' . route('pass-slips.show', $this->passSlip->id))
            ->salutation('Regards,')
            ->subject('📤 Pass Slip Returned: ' . $this->passSlip->slip_number);
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
                'reason' => $this->reason,
                'status' => 'returned',
                'action_url' => route('pass-slips.show', $this->passSlip->id),
            ],
        ];
    }

    public function toArray(User $notifiable): array
    {
        return [
            'title' => 'Pass Slip Returned',
            'body' => "Your pass slip ({$this->passSlip->slip_number}) has been returned. Reason: {$this->reason}",
            'type' => 'pass_slip_returned',
        ];
    }
}