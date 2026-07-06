<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BiometricDeviceController;
use App\Http\Controllers\CompanyContextController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeDocumentController;
use App\Http\Controllers\EmployeeSpreadsheetController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\NitaqatController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\ReportsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::post('/locale', function (Request $request) {
    $locale = $request->validate(['locale' => ['required', 'in:ar,en']])['locale'];

    $request->session()->put('locale', $locale);
    $request->user()?->update(['locale' => $locale]);

    return back();
})->name('locale.update');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    // S1: brute-force guard — 5 attempts per minute per IP.
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:5,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::post('/company-context', [CompanyContextController::class, 'update'])->name('company-context.update');
    Route::post('/notifications/read-all', function (Request $request) {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    })->name('notifications.read-all');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/dashboard/companies/{company}', [DashboardController::class, 'showCompany'])->name('dashboard.companies.show');
    Route::get('/nitaqat/calculator', [NitaqatController::class, 'index'])->name('nitaqat.calculator');
    Route::post('/nitaqat/calculator', [NitaqatController::class, 'calculate'])->name('nitaqat.calculate');
    Route::get('/employees/export', [EmployeeSpreadsheetController::class, 'export'])->name('employees.export');
    Route::get('/employees/import-template', [EmployeeSpreadsheetController::class, 'template'])->name('employees.import-template');
    Route::post('/employees/import', [EmployeeSpreadsheetController::class, 'import'])->name('employees.import');
    Route::resource('employees', EmployeeController::class)->only(['index', 'show', 'create', 'store', 'edit', 'update', 'destroy']);
    Route::post('/employees/{employee}/status', [EmployeeController::class, 'updateStatus'])->name('employees.status');
    Route::resource('leaves', LeaveRequestController::class)->only(['index', 'create', 'store']);
    Route::post('/leaves/{leave}/approve', [LeaveRequestController::class, 'approve'])->name('leaves.approve');
    Route::post('/leaves/{leave}/reject', [LeaveRequestController::class, 'reject'])->name('leaves.reject');
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/devices', [BiometricDeviceController::class, 'index'])->name('devices.index');
    Route::post('/devices', [BiometricDeviceController::class, 'store'])->name('devices.store');
    Route::put('/devices/{device}', [BiometricDeviceController::class, 'update'])->name('devices.update');
    Route::post('/devices/pull-all', [BiometricDeviceController::class, 'pullAll'])->name('devices.pull-all');
    Route::post('/devices/{device}/test', [BiometricDeviceController::class, 'test'])->name('devices.test');
    Route::post('/devices/{device}/pull', [BiometricDeviceController::class, 'pull'])->name('devices.pull');
    Route::delete('/devices/{device}', [BiometricDeviceController::class, 'destroy'])->name('devices.toggle');
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
    Route::get('/payroll/{payroll}/items/{item}/payslip', [PayrollController::class, 'payslip'])->name('payroll.payslip');
    Route::get('/payroll/{payroll}/export/mudad', [PayrollController::class, 'exportMudad'])->name('payroll.export.mudad');
    Route::get('/reports', ReportsController::class)->name('reports.index');
    Route::view('/performance', 'performance.index')->name('performance.index');
});
