<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\BiometricDevice;
use App\Models\Employee;
use App\Models\User;
use App\Services\Biometric\AttendancePullService;
use App\Services\Biometric\BiometricConnector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FakeConnector implements BiometricConnector
{
    public array $punches = [];

    public bool $unreachable = false;

    public function probe(BiometricDevice $device): array
    {
        if ($this->unreachable) {
            throw new \RuntimeException('device unreachable');
        }

        return [
            'serial_number' => 'ZK-TEST-001',
            'device_name' => 'ZKTeco F18',
            'version' => '6.60',
            'device_time' => now()->format('Y-m-d H:i:s'),
        ];
    }

    public function fetchPunches(BiometricDevice $device): array
    {
        if ($this->unreachable) {
            throw new \RuntimeException('device unreachable');
        }

        return $this->punches;
    }
}

class BiometricAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private FakeConnector $fake;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fake = new FakeConnector;
        $this->app->instance(BiometricConnector::class, $this->fake);
    }

    private function admin(): User
    {
        return User::where('email', 'admin@hr.local')->firstOrFail();
    }

    private function device(): BiometricDevice
    {
        return BiometricDevice::where('company_id', 1)->firstOrFail();
    }

    public function test_pull_stores_punches_and_builds_attendance_record(): void
    {
        $this->seed();

        $device = $this->device();
        $employee = Employee::where('company_id', 1)->whereNotNull('biometric_user_id')->firstOrFail();
        $day = Carbon::parse('2026-07-01');

        $this->fake->punches = [
            ['device_user_id' => $employee->biometric_user_id, 'punched_at' => $day->copy()->setTime(8, 30), 'state' => 1, 'punch_type' => 0],
            ['device_user_id' => $employee->biometric_user_id, 'punched_at' => $day->copy()->setTime(17, 2), 'state' => 1, 'punch_type' => 1],
        ];

        $stats = app(AttendancePullService::class)->pullDevice($device);

        $this->assertSame(2, $stats['new']);
        $this->assertSame(0, $stats['unmatched']);

        $record = AttendanceRecord::where('employee_id', $employee->id)
            ->whereDate('work_date', $day)
            ->firstOrFail();

        $this->assertSame('biometric', $record->source);
        $this->assertSame('present', $record->status);
        $this->assertSame('08:30:00', $record->check_in);
        $this->assertSame('17:02:00', $record->check_out);
        // Office shift starts 08:00 + 15 min grace → 8:30 is 15 minutes late.
        $this->assertSame(15, (int) $record->late_minutes);

        $device->refresh();
        $this->assertNotNull($device->last_seen_at);
        $this->assertNotNull($device->last_pulled_at);
        $this->assertNull($device->last_error);
    }

    public function test_pull_is_idempotent_across_repeated_runs(): void
    {
        $this->seed();

        $device = $this->device();
        $employee = Employee::where('company_id', 1)->whereNotNull('biometric_user_id')->firstOrFail();

        $this->fake->punches = [
            ['device_user_id' => $employee->biometric_user_id, 'punched_at' => Carbon::parse('2026-07-01 08:00'), 'state' => 1, 'punch_type' => 0],
        ];

        $service = app(AttendancePullService::class);

        $first = $service->pullDevice($device);
        $second = $service->pullDevice($device);

        $this->assertSame(1, $first['new']);
        $this->assertSame(0, $second['new']);
        $this->assertSame(1, $device->punches()->count());
    }

    public function test_manual_attendance_rows_are_never_overwritten(): void
    {
        $this->seed();

        $device = $this->device();
        $employee = Employee::where('company_id', 1)->whereNotNull('biometric_user_id')->firstOrFail();
        $day = '2026-07-02';

        AttendanceRecord::create([
            'employee_id' => $employee->id,
            'work_date' => $day,
            'check_in' => '09:00',
            'status' => 'present',
            'source' => 'manual',
        ]);

        $this->fake->punches = [
            ['device_user_id' => $employee->biometric_user_id, 'punched_at' => Carbon::parse($day . ' 07:45'), 'state' => 1, 'punch_type' => 0],
        ];

        app(AttendancePullService::class)->pullDevice($device);

        $record = AttendanceRecord::where('employee_id', $employee->id)->whereDate('work_date', $day)->firstOrFail();

        $this->assertSame('manual', $record->source);
        $this->assertStringStartsWith('09:00', $record->check_in);
    }

    public function test_unknown_device_user_is_kept_as_unmatched_punch(): void
    {
        $this->seed();

        $this->fake->punches = [
            ['device_user_id' => '99999', 'punched_at' => Carbon::parse('2026-07-01 08:00'), 'state' => 1, 'punch_type' => 0],
        ];

        $stats = app(AttendancePullService::class)->pullDevice($this->device());

        $this->assertSame(1, $stats['unmatched']);
        $this->assertDatabaseHas('attendance_punches', [
            'device_user_id' => '99999',
            'employee_id' => null,
        ]);
    }

    public function test_unreachable_device_records_error_and_command_survives(): void
    {
        $this->seed();

        $device = $this->device();
        $device->update(['is_active' => true]);
        $this->fake->unreachable = true;

        $this->artisan('hr:pull-attendance', ['--device' => $device->id])->assertFailed();

        $this->assertSame('device unreachable', $device->fresh()->last_error);
    }

    public function test_devices_page_gated_and_lists_all_companies(): void
    {
        $this->seed();

        $this->actingAs($this->admin())
            ->get(route('devices.index'))
            ->assertOk()
            ->assertSee('أجهزة البصمة')
            ->assertSee('بصمة الفرع الرئيسي');

        // hr_manager lacks manage-settings — device config is admin-only.
        $hr = User::where('email', 'hr1@hr.local')->firstOrFail();
        $this->actingAs($hr)->get(route('devices.index'))->assertForbidden();

        $employeeUser = User::create([
            'name' => 'Employee User',
            'email' => 'device.employee@hr.local',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'current_company_id' => 1,
        ]);
        $this->actingAs($employeeUser)->get(route('devices.index'))->assertForbidden();
    }

    public function test_pull_all_devices_endpoint_pulls_active_fleet(): void
    {
        $this->seed();

        $device = $this->device();
        $device->update(['is_active' => true]);

        $employee = Employee::where('company_id', 1)->whereNotNull('biometric_user_id')->firstOrFail();
        $this->fake->punches = [
            ['device_user_id' => $employee->biometric_user_id, 'punched_at' => Carbon::parse('2026-07-03 08:05'), 'state' => 1, 'punch_type' => 0],
        ];

        $this->actingAs($this->admin())
            ->post(route('devices.pull-all'))
            ->assertRedirect(route('devices.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('attendance_punches', [
            'biometric_device_id' => $device->id,
            'device_user_id' => $employee->biometric_user_id,
        ]);

        // hr_manager lacks manage-settings.
        $hr = User::where('email', 'hr1@hr.local')->firstOrFail();
        $this->actingAs($hr)->post(route('devices.pull-all'))->assertForbidden();
    }

    public function test_admin_can_register_device_with_ddns_hostname(): void
    {
        $this->seed();

        $this->actingAs($this->admin())->post(route('devices.store'), [
            'company_id' => 2,
            'name_ar' => 'بصمة مستودع جدة',
            'host' => 'factory-branch.dvrdns.org',
            'port' => 4370,
            'comm_key' => 123,
        ])->assertRedirect(route('devices.index'));

        $this->assertDatabaseHas('biometric_devices', [
            'company_id' => 2,
            'host' => 'factory-branch.dvrdns.org',
            'comm_key' => 123,
            'is_active' => true,
        ]);
    }

    public function test_test_connection_endpoint_updates_device_identity(): void
    {
        $this->seed();

        $device = $this->device();

        $this->actingAs($this->admin())
            ->post(route('devices.test', $device))
            ->assertRedirect(route('devices.index'));

        $device->refresh();
        $this->assertSame('ZK-TEST-001', $device->serial_number);
        $this->assertNotNull($device->last_seen_at);
    }
}
