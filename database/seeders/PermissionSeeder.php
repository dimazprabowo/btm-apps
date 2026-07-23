<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Single source of truth untuk semua permissions.
     *
     * Idempotent — aman dijalankan berulang kali di production:
     *   php artisan db:seed --class=PermissionSeeder
     *
     * Konvensi penamaan:
     *   {entity}_{action}
     *   entity : dashboard, companies, configuration, users, roles, notifications, chat
     *   action : view, create, update, delete, export_excel, export_pdf, send
     *
     * Format ini memudahkan grouping otomatis di UI berdasarkan entity prefix.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Dashboard
            'dashboard_view',

            // Master Data — Perusahaan
            'companies_view',
            'companies_create',
            'companies_update',
            'companies_delete',
            'companies_export_excel',
            'companies_export_pdf',

            // Konfigurasi System
            'configuration_view',
            'configuration_update',
            'configuration_export_excel',
            'configuration_export_pdf',

            // Manajemen User
            'users_view',
            'users_create',
            'users_update',
            'users_delete',
            'users_export_excel',
            'users_export_pdf',
            'users_impersonate',

            // Roles & Permissions
            'roles_view',
            'roles_create',
            'roles_update',
            'roles_delete',
            'roles_export_excel',
            'roles_export_pdf',

            // Notifikasi
            'notifications_view',
            'notifications_send',

            // Chat / Pesan
            'chat_view',
            'chat_create',
            'chat_delete',

            // Manajemen Tugas — Proyek
            'projects_view',
            'projects_view_all',
            'projects_create',
            'projects_update',
            'projects_delete',
            'projects_manage_members',
            'projects_export_excel',
            'projects_export_pdf',

            // Manajemen Tugas — Tugas
            'tasks_view',
            'tasks_view_all',
            'tasks_create',
            'tasks_update',
            'tasks_delete',
            'tasks_assign',
            'tasks_comment',
            'tasks_export_excel',
            'tasks_export_pdf',

            // Profile - Company Management
            'manage_own_company',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
