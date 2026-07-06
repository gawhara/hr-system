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

        $devices = BiometricDevice::with(['company', 'branch'])
            ->withCount('punches')
            ->orderBy('company_id')
            ->orderBy('name_ar')
            ->get()
            ->groupBy('company_id');

        return view('devices.index', [
            'companies' => $companies,
            'devicesByCompany' => $devices,
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

            return redirect()->route('devices.index')->with('status', sprintf(
                'الاتصال ناجح — %s (الرقم التسلسلي: %s)',
                $info['device_name'] ?? 'جهاز ZKTeco',
                $info['serial_number'] ?? 'غير متاح',
            ));
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
