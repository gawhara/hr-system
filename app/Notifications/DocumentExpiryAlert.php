<?php

namespace App\Notifications;

use App\Models\EmployeeDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentExpiryAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public EmployeeDocument $document,
        public int $daysLeft,
    ) {
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        // Mail stays off until SMTP is configured for the deployment
        // (config/hr.php) — offline branches must not error on send.
        if (config('hr.expiry_alert_mail') && $notifiable->email) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toDatabase(object $notifiable): array
    {
        $employee = $this->document->employee;
        $type = $this->document->type;

        return [
            'kind' => 'document_expiry',
            'employee_id' => $employee->id,
            'employee_name_ar' => $employee->name_ar,
            'document_id' => $this->document->id,
            'document_type_ar' => $type?->name_ar,
            'document_type_en' => $type?->name_en,
            'expiry_date' => $this->document->expiry_date?->toDateString(),
            'days_left' => $this->daysLeft,
            'message_ar' => sprintf(
                '%s الخاصة بالموظف %s تنتهي خلال %d يوم (%s).',
                $type?->name_ar ?? 'وثيقة',
                $employee->name_ar,
                max($this->daysLeft, 0),
                $this->document->expiry_date?->format('Y-m-d'),
            ),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $employee = $this->document->employee;
        $type = $this->document->type;

        return (new MailMessage)
            ->subject(sprintf('تنبيه انتهاء وثيقة: %s — %s', $type?->name_ar, $employee->name_ar))
            ->line(sprintf(
                '%s الخاصة بالموظف %s تنتهي بتاريخ %s (متبقي %d يوم).',
                $type?->name_ar ?? 'وثيقة',
                $employee->name_ar,
                $this->document->expiry_date?->format('Y-m-d'),
                max($this->daysLeft, 0),
            ))
            ->action('عرض ملف الموظف', route('employees.show', $employee));
    }
}
