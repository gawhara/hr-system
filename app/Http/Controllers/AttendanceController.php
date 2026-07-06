<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $companyId = $request->user()->current_company_id;
        // S8: malformed ?date= must 422, not 500 deep inside the query.
        $date = $request->validate(['date' => ['nullable', 'date_format:Y-m-d']])['date']
            ?? now()->toDateString();

        // work_date is a DATE column — plain where() keeps the index usable.
        $records = AttendanceRecord::with(['employee.department', 'employee.branch'])
            ->where('work_date', $date)
            ->whereHas('employee', fn ($query) => $query->where('company_id', $companyId))
            ->when(! $user->isHrAdmin(), fn ($query) => $query->whereHas('employee', fn ($employeeQuery) => $employeeQuery->where('user_id', $user->id)))
            ->orderBy('work_date')
            ->paginate(12)
            ->withQueryString();

        $employees = Employee::where('company_id', $companyId)
            ->when(! $user->isHrAdmin(), fn ($query) => $query->where('user_id', $user->id));
        $attendanceRecords = AttendanceRecord::where('work_date', $date)
            ->whereHas('employee', fn ($query) => $query->where('company_id', $companyId)
                ->when(! $user->isHrAdmin(), fn ($employeeQuery) => $employeeQuery->where('user_id', $user->id)));

        $summary = [
            'employees' => (clone $employees)->count(),
            'present' => (clone $attendanceRecords)->where('status', 'present')->count(),
            'late' => (clone $attendanceRecords)->where('late_minutes', '>', 0)->count(),
            'absent' => (clone $attendanceRecords)->where('status', 'absent')->count(),
        ];

        return view('attendance.index', [
            'records' => $records,
            'summary' => $summary,
            'date' => $date,
        ]);
    }
}
