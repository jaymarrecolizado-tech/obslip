<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Certificate;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CertificateVerifiedNotification extends Notification
{
    public function __construct(
        public Certificate $certificate
    ) {}

    public function via(User $notifiable): array
    {
        return ['mail', 'database', 'fcm'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Certificate Verified: ' . $this->certificate->passSlip->slip_number)
            ->greeting('Dear ' . $notifiable->name . ',)
            ->line('Great news! Your certificate has been verified.')
            ->line('')
            ->line('Pass Slip: ' . $this->certificate->passSlip->slip_number)
            ->line('Certificate Type: ' . $this->certificate->type->getLabel())
            ->line('Office: ' . $this->certificate->office_name)
            ->line('Verified by: ' . $this->certificate->verifiedBy->name)
            ->line('Verified At: ' . $this->certificate->verified_at->format('F j, Y g:i A'))
            ->line('')
            ->line('Your pass slip is now complete!')
            ->action('View certificate: ' . route('certificates.show', $this->certificate->id))
            ->salutation('Congratulations,')
            ->subject('✅ Certificate Verified: ' . $this->certificate->passSlip->slip_number);
    }

    public function toDatabase(User $notifiable): array
    {
        return [
            'notifiable' => User::class,
            'notifiable_id' => $notifiable->id,
            'data' => [
                'certificate_id' => $this->certificate->id,
                'pass_slip_id' => $this->certificate->passSlip->id,
                'slip_number' => $this->certificate->passSlip->slip_number,
                'office_name' => $this->certificate->office_name,
                'status' => 'verified',
            ],
        ];
    }

    public function toArray(User $notifiable): array
    {
        return [
            'title' => 'Certificate Verified',
            'body' => "Your certificate for {$this->certificate->passSlip->slip_number} has been verified. Pass slip completed!",
            'type' => 'certificate_verified',
        ];
    }
}