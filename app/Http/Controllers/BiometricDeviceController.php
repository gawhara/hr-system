<?php

namespace App\Http\Controllers;

use App\Models\BiometricDevice;
use App\Models\Branch;
use App\Models\Company;
use App\Services\Biometric\AttendancePullService;
use App\Services\Biometric\BiometricConnector;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BiometricDeviceController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('manage-settings'), 403);

        $companies = Company::orderBy('id')
            ->with(['branches' => fn ($query) => $query->orderBy('name_ar')])
            ->get();

        $allDevices = BiometricDevice::with(['company', 'branch'])
            ->withCount('punches')
            ->orderBy('company_id')
            ->orderBy('name_ar')
            ->get();

        // Punches the puller couldn't attribute — each row is a device user
        // id that needs its employee's "رقم المستخدم في جهاز البصمة" filled.
        $unmatched = \App\Models\AttendancePunch::query()
            ->whereNull('employee_id')
            ->selectRaw('biometric_device_id, device_user_id, count(*) as punch_count, max(punched_at) as last_punch_at')
            ->groupBy('biometric_device_id', 'device_user_id')
            ->orderByDesc('punch_count')
            ->limit(20)
            ->get()
            ->each(fn ($row) => $row->setRelation(
                'device',
                $allDevices->firstWhere('id', $row->biometric_device_id),
            ));

        return view('devices.index', [
            'companies' => $companies,
            'devicesByCompany' => $allDevices->groupBy('company_id'),
            'fleet' => [
                'total' => $allDevices->count(),
                'active' => $allDevices->where('is_active', true)->count(),
                'online' => $allDevices->filter(fn ($device) => $device->connectionStatus() === 'online')->count(),
                'errors' => $allDevices->whereNotNull('last_error')->count(),
                'punches' => (int) $allDevices->sum('punches_count'),
                'unmatched' => (int) \App\Models\AttendancePunch::whereNull('employee_id')->count(),
            ],
            'unmatched' => $unmatched,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('manage-settings'), 403);

        BiometricDevice::create($this->validated($request));

        return redirect()->route('devices.index')->with('status', 'تمت إضافة الجهاز بنجاح.');
    }

    public function update(Request $request, BiometricDevice $device)
    {
        abort_unless($request->user()->can('manage-settings'), 403);

        $device->update($this->validated($request, $device) + ['last_error' => null]);

        return redirect()->route('devices.index')->with('status', 'تم تحديث بيانات الجهاز.');
    }

    /**
     * Live reachability probe — LAN, VPN, static IP, or DDNS alike.
     */
    public function test(Request $request, BiometricDevice $device, BiometricConnector $connector)
    {
        abort_unless($request->user()->can('manage-settings'), 403);

        try {
            $info = $connector->probe($device);

            $device->forceFill([
                'last_seen_at' => now(),
                'last_error' => null,
                'serial_number' => $info['serial_number'] ?? $device->serial_number,
                'model' => $info['device_name'] ?? $device->model,
            ])->save();

            $message = sprintf(
                'الاتصال ناجح — %s | الرقم التسلسلي: %s | الإصدار: %s',
                $info['device_name'] ?? 'جهاز ZKTeco',
                $info['serial_number'] ?? 'غير متاح',
                $info['version'] ?? 'غير متاح',
            );

            // Attendance is only as accurate as the device clock: surface
            // drift immediately instead of discovering it in payroll.
            if (! empty($info['device_time'])) {
                $driftMinutes = (int) round(abs(now()->diffInSeconds(\Illuminate\Support\Carbon::parse($info['device_time']), false)) / 60);

                $message .= $driftMinutes >= 3
                    ? sprintf(' | ⚠ ساعة الجهاز منحرفة %d دقيقة عن الخادم (%s) — اضبط وقت الجهاز قبل الاعتماد على الحضور.', $driftMinutes, $info['device_time'])
                    : sprintf(' | ساعة الجهاز مطابقة (%s)', $info['device_time']);
            }

            return redirect()->route('devices.index')->with('status', $message);
        } catch (\RuntimeException $exception) {
            $device->update(['last_error' => $exception->getMessage()]);

            return redirect()->route('devices.index')->withErrors(['device' => $exception->getMessage()]);
        }
    }

    public function pull(Request $request, BiometricDevice $device, AttendancePullService $service)
    {
        abort_unless($request->user()->can('manage-settings'), 403);

        try {
            $stats = $service->pullDevice($device);

            return redirect()->route('devices.index')->with('status', sprintf(
                'تم سحب البصمات: %d سجل، %d جديد، %d بدون موظف مطابق، %d يوم أعيد احتسابه.',
                $stats['fetched'],
                $stats['new'],
                $stats['unmatched'],
                $stats['days'],
            ));
        } catch (\RuntimeException $exception) {
            return redirect()->route('devices.index')->withErrors(['device' => $exception->getMessage()]);
        }
    }

    /**
     * Manual fleet-wide pull (the scheduler also runs one every 15 min).
     */
    public function pullAll(Request $request, AttendancePullService $service)
    {
        abort_unless($request->user()->can('manage-settings'), 403);

        $results = [];
        $failures = 0;

        foreach (BiometricDevice::where('is_active', true)->get() as $device) {
            try {
                $stats = $service->pullDevice($device);
                $results[] = sprintf('%s: %d جديد', $device->name_ar, $stats['new']);
            } catch (\RuntimeException) {
                $failures++;
                $results[] = sprintf('%s: تعذر الاتصال', $device->name_ar);
            }
        }

        if ($results === []) {
            return redirect()->route('devices.index')->withErrors(['device' => 'لا توجد أجهزة مفعلة للسحب.']);
        }

        $summary = 'اكتمل السحب — ' . implode(' | ', $results);

        return $failures === count($results)
            ? redirect()->route('devices.index')->withErrors(['device' => $summary])
            : redirect()->route('devices.index')->with('status', $summary);
    }

    public function destroy(Request $request, BiometricDevice $device)
    {
        abort_unless($request->user()->can('manage-settings'), 403);

        // Deactivate, don't delete — punches must stay attributable.
        $device->update(['is_active' => ! $device->is_active]);

        return redirect()->route('devices.index')->with(
            'status',
            $device->is_active ? 'تم تفعيل الجهاز.' : 'تم إيقاف الجهاز — لن يُسحب منه حضور حتى إعادة تفعيله.',
        );
    }

    private function validated(Request $request, ?BiometricDevice $device = null): array
    {
        return $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'branch_id' => [
                'nullable', 'exists:branches,id',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value && Branch::find($value)?->company_id !== (int) $request->input('company_id')) {
                        $fail(__('الفرع المحدد لا يتبع الشركة المختارة.'));
                    }
                },
            ],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            // IP address or DDNS hostname — both are just resolvable hosts.
            'host' => [
                'required', 'string', 'max:255', 'regex:/^[A-Za-z0-9][A-Za-z0-9\.\-]*$/',
                Rule::unique('biometric_devices', 'host')
                    ->where('port', (int) $request->input('port', 4370))
                    ->ignore($device),
            ],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'comm_key' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'is_active' => ['nullable', 'boolean'],
        ]) + [
            'comm_key' => (int) $request->input('comm_key', 0),
            'is_active' => $request->boolean('is_active', true),
        ];
    }
}
