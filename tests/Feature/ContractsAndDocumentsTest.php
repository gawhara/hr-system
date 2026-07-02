<?php

namespace Tests\Feature;

use App\Models\DocumentType;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContractsAndDocumentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_admin_can_register_document_with_file_upload(): void
    {
        Storage::fake('local');
        $this->seed();
        $admin = User::where('email', 'admin@hr.local')->firstOrFail();
        $employee = Employee::firstOrFail();
        $type = DocumentType::where('key', 'work_permit')->firstOrFail();

        $response = $this->actingAs($admin)->post(route('employees.documents.store', $employee), [
            'document_type_id' => $type->id,
            'document_number' => 'WP-1234',
            'expiry_date' => now()->addMonths(6)->toDateString(),
            'file' => UploadedFile::fake()->create('permit.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect(route('employees.show', $employee));

        $document = $employee->documents()->where('document_number', 'WP-1234')->first();
        $this->assertNotNull($document);
        $this->assertNotNull($document->uuid);
        Storage::disk('local')->assertExists($document->file_path);
    }

    public function test_expiry_date_required_when_type_requires_it(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@hr.local')->firstOrFail();
        $employee = Employee::firstOrFail();
        $type = DocumentType::where('key', 'iqama')->firstOrFail();

        $this->actingAs($admin)
            ->from(route('employees.documents.create', $employee))
            ->post(route('employees.documents.store', $employee), [
                'document_type_id' => $type->id,
            ])
            ->assertSessionHasErrors('expiry_date');
    }

    public function test_employee_cannot_manage_documents_but_can_download_own(): void
    {
        Storage::fake('local');
        $this->seed();
        $admin = User::where('email', 'admin@hr.local')->firstOrFail();
        $employee = Employee::whereNull('user_id')->firstOrFail();
        $type = DocumentType::where('key', 'passport')->firstOrFail();

        $user = User::create([
            'name' => 'Employee User',
            'email' => 'doc.employee@hr.local',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'current_company_id' => $employee->company_id,
        ]);
        $user->companies()->sync([$employee->company_id]);
        $employee->forceFill(['user_id' => $user->id])->save();

        $this->actingAs($admin)->post(route('employees.documents.store', $employee), [
            'document_type_id' => $type->id,
            'expiry_date' => now()->addYear()->toDateString(),
            'file' => UploadedFile::fake()->create('passport.pdf', 50, 'application/pdf'),
        ])->assertRedirect();

        $document = $employee->documents()->whereNotNull('file_path')->firstOrFail();

        $this->actingAs($user)->get(route('employees.documents.create', $employee))->assertForbidden();
        $this->actingAs($user)->delete(route('documents.destroy', $document))->assertForbidden();
        $this->actingAs($user)->get(route('documents.download', $document))->assertOk();
    }

    public function test_contract_creation_and_termination_flow(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@hr.local')->firstOrFail();
        $employee = Employee::firstOrFail();

        $this->actingAs($admin)->post(route('employees.contracts.store', $employee), [
            'contract_type' => 'fixed',
            'starts_on' => now()->toDateString(),
            'ends_on' => now()->addYear()->toDateString(),
            'basic_salary' => 7000,
        ])->assertRedirect(route('employees.show', $employee));

        $contract = $employee->contracts()->latest('id')->firstOrFail();
        $this->assertSame('active', $contract->status);
        $this->assertSame($employee->company_id, $contract->company_id);

        $this->actingAs($admin)->post(route('contracts.terminate', $contract), [
            'termination_reason' => 'انتهاء المشروع',
        ])->assertRedirect();

        $contract->refresh();
        $this->assertSame('terminated', $contract->status);
        $this->assertNotNull($contract->terminated_at);

        // Termination must land in the audit trail (AGENT.md security reqs).
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'contract',
            'subject_id' => $contract->id,
            'event' => 'updated',
            'causer_id' => $admin->id,
        ]);
    }

    public function test_fixed_contract_requires_end_date(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@hr.local')->firstOrFail();
        $employee = Employee::firstOrFail();

        $this->actingAs($admin)
            ->from(route('employees.contracts.create', $employee))
            ->post(route('employees.contracts.store', $employee), [
                'contract_type' => 'fixed',
                'starts_on' => now()->toDateString(),
                'basic_salary' => 7000,
            ])
            ->assertSessionHasErrors('ends_on');
    }
}
