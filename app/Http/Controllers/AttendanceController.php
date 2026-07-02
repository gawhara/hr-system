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
        $date = $request->query('date', now()->toDateString());

        $records = AttendanceRecord::with(['employee.department', 'employee.branch'])
            ->whereDate('work_date', $date)
            ->whereHas('employee', fn ($query) => $query->where('company_id', $companyId))
            ->when(! $user->isHrAdmin(), fn ($query) => $query->whereHas('employee', fn ($employeeQuery) => $employeeQuery->where('user_id', $user->id)))
            ->orderBy('work_date')
            ->paginate(12)
            ->withQueryString();

        $employees = Employee::where('company_id', $companyId)
            ->when(! $user->isHrAdmin(), fn ($query) => $query->where('user_id', $user->id));
        $attendanceRecords = AttendanceRecord::whereDate('work_date', $date)
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
