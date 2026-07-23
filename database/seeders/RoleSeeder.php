<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Super Admin - All permissions (bypass via Gate::before in AuthServiceProvider)
        $superAdmin = Role::firstOrCreate(['name' => 'super admin']);
        $superAdmin->syncPermissions(Permission::all());

        // 2. Admin - Full Access (explicit permissions)
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions(Permission::all());

        // 3. Manajer Proyek - Oversight lintas proyek (bukan admin sistem).
        //    Bisa melihat & mengelola SEMUA proyek dan tugas via permission
        //    projects_view_all / tasks_view_all, tanpa harus jadi anggota.
        $projectManager = Role::firstOrCreate(['name' => 'manajer proyek']);
        $projectManager->syncPermissions([
            'dashboard_view',
            'projects_view',
            'projects_view_all',
            'projects_create',
            'projects_update',
            'projects_delete',
            'projects_manage_members',
            'projects_export_excel',
            'projects_export_pdf',
            'tasks_view',
            'tasks_view_all',
            'tasks_create',
            'tasks_update',
            'tasks_delete',
            'tasks_assign',
            'tasks_comment',
            'tasks_export_excel',
            'tasks_export_pdf',
        ]);

        // 4. User - Basic task management permissions (hanya proyek yang diikuti)
        $user = Role::firstOrCreate(['name' => 'user']);
        $user->syncPermissions([
            'dashboard_view',
            'notifications_view',
            'projects_view',
            'tasks_view',
            'tasks_create',
            'tasks_update',
            'tasks_assign',
            'tasks_comment',
        ]);
    }
}
