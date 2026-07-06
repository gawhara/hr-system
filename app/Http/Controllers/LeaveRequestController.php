<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaveRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $companyId = $request->user()->current_company_id;

        $leaveRequests = LeaveRequest::with(['employee.company', 'employee.department', 'leaveType', 'approver'])
            ->whereHas('employee', fn ($query) => $query->where('company_id', $companyId))
            ->when(! $user->isHrAdmin(), fn ($query) => $query->whereHas('employee', fn ($employeeQuery) => $employeeQuery->where('user_id', $user->id)))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('leaves.index', [
            'leaveRequests' => $leaveRequests,
            'status' => $request->query('status', ''),
        ]);
    }

    public function create(Request $request)
    {
        $user = $request->user();
        $companyId = $request->user()->current_company_id;

        return view('leaves.create', [
            'employees' => Employee::where('company_id', $companyId)
                ->when(! $user->isHrAdmin(), fn ($query) => $query->where('user_id', $user->id))
                ->orderBy('name_ar')
                ->get(),
            'leaveTypes' => LeaveType::orderBy('name_ar')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $employee = Employee::findOrFail($data['employee_id']);
        abort_unless($employee->company_id === $request->user()->current_company_id, 403);
        abort_unless($request->user()->isHrAdmin() || $employee->user_id === $request->user()->id, 403);

        // B2: one employee can't hold two overlapping pending/approved leaves.
        $overlaps = LeaveRequest::where('employee_id', $data['employee_id'])
            ->whereIn('status', ['pending', 'approved'])
            ->where('starts_on', '<=', $data['ends_on'])
            ->where('ends_on', '>=', $data['starts_on'])
            ->exists();

        if ($overlaps) {
            return back()
                ->withInput()
                ->withErrors(['starts_on' => 'يوجد طلب إجازة آخر (معلق أو معتمد) يتداخل مع هذه الفترة.']);
        }

        // Calendar days by policy; working-day calendars are a future item.
        $days = now()->parse($data['starts_on'])->diffInDays(now()->parse($data['ends_on'])) + 1;

        LeaveRequest::create($data + [
            'days' => $days,
            'status' => 'pending',
        ]);

        return redirect()->route('leaves.index')->with('status', 'تم إنشاء طلب الإجازة.');
    }

    public function approve(Request $request, LeaveRequest $leave)
    {
        abort_unless($request->user()->canApproveLeaveRequests(), 403);
        abort_unless($leave->employee()->where('company_id', $request->user()->current_company_id)->exists(), 403);

        DB::transaction(function () use ($leave, $request) {
            $leave->load('employee');

            $balance = LeaveBalance::firstOrCreate([
                'employee_id' => $leave->employee_id,
                'leave_type_id' => $leave->leave_type_id,
                'year' => (int) $leave->starts_on->format('Y'),
            ], [
                'entitled_days' => 0,
                'used_days' => 0,
                'carried_days' => 0,
            ]);

            if ($leave->status !== 'approved') {
                // B2: paid leave must not drive the balance negative silently.
                $remaining = (float) $balance->entitled_days + (float) $balance->carried_days - (float) $balance->used_days;

                if ($leave->leaveType()->value('is_paid') && $remaining < (float) $leave->days) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'leave' => sprintf(
                            'رصيد الإجازة غير كافٍ للموافقة: المتبقي %.1f يوم والطلب %.1f يوم.',
                            $remaining,
                            (float) $leave->days,
                        ),
                    ]);
                }

                $balance->increment('used_days', $leave->days);
            }

            $leave->forceFill([
                'status' => 'approved',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ])->save();
        });

        return back()->with('status', 'تمت الموافقة على طلب الإجازة.');
    }

    public function reject(Request $request, LeaveRequest $leave)
    {
        abort_unless($request->user()->canApproveLeaveRequests(), 403);
        abort_unless($leave->employee()->where('company_id', $request->user()->current_company_id)->exists(), 403);

        $leave->forceFill([
            'status' => 'rejected',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ])->save();

        return back()->with('status', 'تم رفض طلب الإجازة.');
    }
}
