<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EmployeeDocumentController extends Controller
{
    public function create(Request $request, Employee $employee)
    {
        $this->authorizeManage($request, $employee);

        return view('documents.create', [
            'employee' => $employee,
            'documentTypes' => DocumentType::where('is_active', true)->orderBy('name_ar')->get(),
        ]);
    }

    public function store(Request $request, Employee $employee)
    {
        $this->authorizeManage($request, $employee);

        $data = $request->validate([
            'document_type_id' => ['required', Rule::exists('document_types', 'id')->where('is_active', true)],
            'document_number' => ['nullable', 'string', 'max:255'],
            'issue_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $type = DocumentType::findOrFail($data['document_type_id']);

        if ($type->requires_expiry && empty($data['expiry_date'])) {
            return back()->withInput()->withErrors([
                'expiry_date' => 'تاريخ الانتهاء مطلوب لهذا النوع من الوثائق.',
            ]);
        }

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            // Local (private) disk: offline-first — files live at the branch,
            // central upload happens with the sync service later.
            $data['file_path'] = $file->store("employee-documents/{$employee->uuid}", 'local');
            $data['original_file_name'] = $file->getClientOriginalName();
        }

        unset($data['file']);

        $employee->documents()->create($data + ['created_by' => $request->user()->id]);

        return redirect()
            ->route('employees.show', $employee)
            ->with('status', 'تمت إضافة الوثيقة بنجاح.');
    }

    public function download(Request $request, EmployeeDocument $document)
    {
        $user = $request->user();

        abort_unless(
            $user->canViewEmployee($document->employee) && ($user->can('view-documents') || $document->employee->user_id === $user->id),
            403,
        );
        abort_unless($document->file_path && Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download($document->file_path, $document->original_file_name);
    }

    public function destroy(Request $request, EmployeeDocument $document)
    {
        $this->authorizeManage($request, $document->employee);

        // Soft delete only — the file stays on disk and the audit log keeps
        // the trail; HR records must never be hard-deleted.
        $document->delete();

        return redirect()
            ->route('employees.show', $document->employee)
            ->with('status', 'تم حذف الوثيقة.');
    }

    private function authorizeManage(Request $request, Employee $employee): void
    {
        abort_unless($request->user()->can('manage-documents'), 403);
        abort_unless($request->user()->canAccessCompany($employee->company_id), 403);
    }
}
