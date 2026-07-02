<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyContextController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\EmployeeDocumentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\NitaqatController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\ReportsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::post('/locale', function (\Illuminate\Http\Request $request) {
    $locale = $request->validate(['locale' => ['required', 'in:ar,en']])['locale'];

    $request->session()->put('locale', $locale);
    $request->user()?->update(['locale' => $locale]);

    return back();
})->name('locale.update');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::post('/company-context', [CompanyContextController::class, 'update'])->name('company-context.update');
    Route::post('/notifications/read-all', function (\Illuminate\Http\Request $request) {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    })->name('notifications.read-all');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/dashboard/companies/{company}', [DashboardController::class, 'showCompany'])->name('dashboard.companies.show');
    Route::get('/nitaqat/calculator', [NitaqatController::class, 'index'])->name('nitaqat.calculator');
    Route::post('/nitaqat/calculator', [NitaqatController::class, 'calculate'])->name('nitaqat.calculate');
    Route::resource('employees', EmployeeController::class)->only(['index', 'show', 'create', 'store', 'edit', 'update']);
    Route::post('/employees/{employee}/status', [EmployeeController::class, 'updateStatus'])->name('employees.status');
    Route::resource('leaves', LeaveRequestController::class)->only(['index', 'create', 'store']);
    Route::post('/leaves/{leave}/approve', [LeaveRequestController::class, 'approve'])->name('leaves.approve');
    Route::post('/leaves/{leave}/reject', [LeaveRequestController::class, 'reject'])->name('leaves.reject');
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::get('/employees/{employee}/documents/create', [EmployeeDocumentController::class, 'create'])->name('employees.documents.create');
    Route::post('/employees/{employee}/documents', [EmployeeDocumentController::class, 'store'])->name('employees.documents.store');
    Route::get('/documents/{document}/download', [EmployeeDocumentController::class, 'download'])->name('documents.download');
    Route::delete('/documents/{document}', [EmployeeDocumentController::class, 'destroy'])->name('documents.destroy');
    Route::get('/employees/{employee}/contracts/create', [ContractController::class, 'create'])->name('employees.contracts.create');
    Route::post('/employees/{employee}/contracts', [ContractController::class, 'store'])->name('employees.contracts.store');
    Route::post('/contracts/{contract}/terminate', [ContractController::class, 'terminate'])->name('contracts.terminate');
    Route::resource('payroll', PayrollController::class)->only(['index', 'show']);
    Route::post('/payroll/{payroll}/transition', [PayrollController::class, 'transition'])->name('payroll.transition');
    Route::post('/payroll/{payroll}/adjustment', [PayrollController::class, 'createAdjustment'])->name('payroll.adjustment');
    Route::get('/reports', ReportsController::class)->name('reports.index');
    Route::view('/recruitment', 'recruitment.index')->name('recruitment.index');
    Route::view('/performance', 'performance.index')->name('performance.index');
});
