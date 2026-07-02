<?php

namespace App\Console\Commands;

use App\Models\EmployeeDocument;
use App\Models\User;
use App\Notifications\DocumentExpiryAlert;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class SendDocumentExpiryAlerts extends Command
{
    protected $signature = 'hr:send-expiry-alerts';

    protected $description = 'Send in-app/mail alerts for documents crossing their configured expiry lead times';

    /**
     * Threshold rule: a document "crosses" every configured lead time (from
     * document_types.alert_days) where days_left <= threshold. One alert is
     * sent for the smallest crossed threshold, and every crossed threshold is
     * recorded so nothing re-fires — even when a branch was offline long
     * enough to skip past several thresholds.
     */
    public function handle(): int
    {
        $sent = 0;

        EmployeeDocument::with(['type', 'employee.company'])
            ->whereNotNull('expiry_date')
            ->whereHas('type', fn ($query) => $query->whereNotNull('alert_days')->where('is_active', true))
            ->chunkById(100, function (Collection $documents) use (&$sent) {
                foreach ($documents as $document) {
                    $sent += $this->processDocument($document);
                }
            });

        $this->info("Expiry alerts sent: {$sent}");

        return self::SUCCESS;
    }

    private function processDocument(EmployeeDocument $document): int
    {
        $daysLeft = $document->daysLeft();

        if ($daysLeft === null || $daysLeft < 0) {
            return 0;
        }

        $thresholds = collect($document->type->alert_days ?? [])
            ->map(fn ($days) => (int) $days)
            ->filter(fn (int $days) => $daysLeft <= $days)
            ->sort()
            ->values();

        if ($thresholds->isEmpty()) {
            return 0;
        }

        $alreadyNotified = $document->expiryAlerts()->pluck('threshold_days');
        $newThresholds = $thresholds->reject(fn (int $days) => $alreadyNotified->contains($days));

        if ($newThresholds->isEmpty()) {
            return 0;
        }

        foreach ($this->recipients($document) as $recipient) {
            $recipient->notify(new DocumentExpiryAlert($document, $daysLeft));
        }

        foreach ($newThresholds as $threshold) {
            $document->expiryAlerts()->create([
                'threshold_days' => $threshold,
                'notified_at' => now(),
            ]);
        }

        return 1;
    }

    /**
     * HR-side recipients scoped to the employee's company, plus (optionally)
     * the employee's own user account.
     */
    private function recipients(EmployeeDocument $document): Collection
    {
        $companyId = $document->employee->company_id;

        $recipients = User::role(['group_admin', 'company_admin', 'hr_manager'])
            ->get()
            ->filter(fn (User $user) => $user->canAccessCompany($companyId));

        if (config('hr.expiry_alert_notify_employee') && $document->employee->user_id) {
            $employeeUser = User::find($document->employee->user_id);

            if ($employeeUser) {
                $recipients->push($employeeUser);
            }
        }

        return $recipients->unique('id')->values();
    }
}
