<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Certificate;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CertificateSubmittedNotification extends Notification
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
            ->subject('Certificate Submitted: ' . $this->certificate->passSlip->slip_number)
            ->greeting('Dear ' . $notifiable->name . ',)
            ->line('A certificate has been submitted for verification.')
            ->line('')
            ->line('Pass Slip: ' . $this->certificate->passSlip->slip_number)
            ->line('Certificate Type: ' . $this->certificate->type->getLabel())
            ->line('Office: ' . $this->certificate->office_name)
            ->line('Representative: ' . $this->certificate->representative_name)
            ->line('')
            ->line('Please review and verify the certificate.')
            ->action('View certificate: ' . route('certificates.show', $this->certificate->id))
            ->salutation('Regards,')
            ->subject('📄 Certificate Submitted: ' . $this->certificate->passSlip->slip_number);
    }

    public function toDatabase(User $notifiable): array
    {
        return [
            'notifiable_type' => User::class,
            'notifiable_id' => $notifiable->id,
            'data' => [
                'certificate_id' => $this->certificate->id,
                'pass_slip_id' => $this->certificate->passSlip->id,
                'slip_number' => $this->certificate->passSlip->slip_number,
                'office_name' => $this->certificate->office_name,
                'representative' => $this->certificate->representative_name,
                'status' => 'submitted',
            ],
        ];
    }

    public function toArray(User $notifiable): array
    {
        return [
            'title' => 'Certificate Submitted',
            'body' => "Certificate submitted for {$this->certificate->passSlip->slip_number}.",
            'type' => 'certificate_submitted',
        ];
    }
}