<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'admin',
            'manager',
            'hr',
            'employee',
            'finance',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        $permissions = [
            'view-role', 'create-role', 'edit-role', 'delete-role',

            'view-position', 'create-position', 'edit-position', 'delete-position',

            'view-department', 'create-department', 'edit-department', 'delete-department',

            'view-employee', 'create-employee', 'edit-employee', 'delete-employee',

            'view-leave-type', 'create-leave-type', 'edit-leave-type', 'delete-leave-type',

            'view-leave-request', 'create-leave-request', 'edit-leave-request', 'delete-leave-request', 'handle-leave-request',

            'view-salary-config', 'create-salary-config', 'edit-salary-config', 'delete-salary-config',

            'view-salary', 'create-payroll', 'edit-payroll', 'delete-payroll',

            'view-leave-balance',
            
            'create-attendance',
            'mark-absence'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        Role::findByName('admin')->givePermissionTo(Permission::all());

        $employeePermissions = [
            'view-position',
            'view-department',
            'view-employee',
            'view-leave-type',
            'view-leave-request',
            'create-leave-request',
            'edit-leave-request',
            'delete-leave-request',
            'view-leave-balance',
            'view-salary',
        ];
        Role::findByName('employee')->givePermissionTo($employeePermissions);

        $hrPermissions = array_merge($employeePermissions, [
            'create-employee', 'edit-employee', 'delete-employee',
            'handle-leave-request',
            'create-attendance',
            'mark-absence'
        ]);
        Role::findByName('hr')->givePermissionTo($hrPermissions);

        $managerPermissions = ['handle-leave-request', 'mark-absence'];
        Role::findByName('manager')->givePermissionTo($managerPermissions);

        $financePermissions = [
            'view-salary-config', 'create-salary-config', 'edit-salary-config', 'delete-salary-config',
            'create-payroll', 'edit-payroll', 'delete-payroll',
        ];
        Role::findByName('finance')->givePermissionTo($financePermissions);
    }
}