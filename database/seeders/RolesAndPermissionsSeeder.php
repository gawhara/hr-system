<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Role set per AGENT.md: Group Admin, Company Admin, HR Manager,
     * Branch Manager, Payroll Officer, Employee (self-service).
     * Company/branch scoping is enforced at the query level via the
     * company_user pivot and controller scopes, not via spatie teams.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'view-sensitive-hr',
            'manage-employees',
            'view-attendance-all',
            'approve-leave',
            'view-payroll',
            'manage-payroll',
            'view-documents',
            'manage-documents',
            'view-reports',
            'view-nitaqat',
            'manage-settings',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $rolePermissions = [
            'group_admin' => $permissions,
            'company_admin' => $permissions,
            'hr_manager' => [
                'view-sensitive-hr',
                'manage-employees',
                'view-attendance-all',
                'approve-leave',
                'view-payroll',
                'manage-payroll',
                'view-documents',
                'manage-documents',
                'view-reports',
                'view-nitaqat',
            ],
            // Branch managers get branch-scoped attendance/leave visibility once
            // users are linked to branches; until then they see only their own
            // records, same as employees, so no broad grants here.
            'branch_manager' => [],
            'payroll_officer' => [
                'view-payroll',
                'manage-payroll',
                'view-reports',
            ],
            'employee' => [],
        ];

        foreach ($rolePermissions as $role => $granted) {
            Role::findOrCreate($role)->syncPermissions($granted);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
