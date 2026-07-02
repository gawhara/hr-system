<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('view-documents'), 403);

        $user = $request->user();
        $companyIds = $user->isGroupAdmin()
            ? Company::pluck('id')
            : $user->companies()->pluck('companies.id');

        $employees = Employee::with(['company', 'department'])
            ->whereIn('company_id', $companyIds)
            ->get();

        $documents = collect();

        foreach ($employees as $employee) {
            foreach ([
                'iqama_expiry' => ['label' => 'الإقامة', 'icon' => 'badge'],
                'passport_expiry' => ['label' => 'جواز السفر', 'icon' => 'travel_explore'],
                'contract_end_date' => ['label' => 'العقد', 'icon' => 'contract'],
            ] as $field => $meta) {
                $date = $employee->{$field};

                if (! $date) {
                    continue;
                }

                $daysLeft = now()->startOfDay()->diffInDays($date->copy()->startOfDay(), false);

                $documents->push([
                    'employee' => $employee,
                    'type' => $meta['label'],
                    'icon' => $meta['icon'],
                    'expires_on' => $date,
                    'days_left' => (int) $daysLeft,
                    'status' => match (true) {
                        $daysLeft < 0 => 'expired',
                        $daysLeft <= 15 => 'urgent',
                        $daysLeft <= 45 => 'soon',
                        default => 'healthy',
                    },
                ]);
            }
        }

        // Uploaded/registered documents (employee_documents) join the
        // employee-record dates (iqama/passport/contract) in one expiry list.
        EmployeeDocument::with(['employee.company', 'employee.department', 'type'])
            ->whereHas('employee', fn ($query) => $query->whereIn('company_id', $companyIds))
            ->whereNotNull('expiry_date')
            ->get()
            ->each(function (EmployeeDocument $document) use ($documents) {
                $documents->push([
                    'employee' => $document->employee,
                    'type' => $document->type?->name_ar ?? 'وثيقة',
                    'icon' => $document->type?->icon ?? 'description',
                    'expires_on' => $document->expiry_date,
                    'days_left' => $document->daysLeft(),
                    'status' => $document->expiryStatus(),
                ]);
            });

        $sortedDocuments = $documents
            ->sortBy('days_left')
            ->values();

        return view('documents.index', [
            'documents' => $sortedDocuments,
            'visibleDocuments' => $sortedDocuments->take(20),
            'metrics' => [
                'total' => $documents->count(),
                'expired' => $documents->where('status', 'expired')->count(),
                'urgent' => $documents->where('status', 'urgent')->count(),
                'soon' => $documents->where('status', 'soon')->count(),
                'healthy' => $documents->where('status', 'healthy')->count(),
            ],
            'forecast' => [
                '15' => $documents->filter(fn ($document) => $document['days_left'] >= 0 && $document['days_left'] <= 15)->count(),
                '30' => $documents->filter(fn ($document) => $document['days_left'] >= 0 && $document['days_left'] <= 30)->count(),
                '45' => $documents->filter(fn ($document) => $document['days_left'] >= 0 && $document['days_left'] <= 45)->count(),
            ],
        ]);
    }
}
