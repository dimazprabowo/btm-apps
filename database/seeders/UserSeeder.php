<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@app.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'phone' => '021-1234566',
                'position' => 'Super Administrator',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        if (!$superAdmin->hasRole('super admin')) {
            $superAdmin->assignRole('super admin');
        }

        // Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@app.com'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
                'phone' => '021-1234567',
                'position' => 'System Administrator',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        if (!$admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        // Manajer Proyek — bisa melihat & mengelola semua proyek/tugas,
        // tanpa jadi anggota (demonstrasi permission projects_view_all).
        $projectManager = User::firstOrCreate(
            ['email' => 'manajer@app.com'],
            [
                'name' => 'Manajer Proyek',
                'password' => Hash::make('password'),
                'phone' => '021-1234569',
                'position' => 'Manajer Proyek',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        if (!$projectManager->hasRole('manajer proyek')) {
            $projectManager->assignRole('manajer proyek');
        }

        // 3 User proyek — masing-masing pemilik 1 proyek
        $projectUsers = [
            ['email' => 'user1@app.com',  'name' => 'User 1',   'phone' => '0811-111111', 'position' => 'Frontend Developer'],
            ['email' => 'user2@app.com',  'name' => 'User 2',   'phone' => '0811-222222', 'position' => 'Mobile Developer'],
            ['email' => 'user3@app.com', 'name' => 'User 3',   'phone' => '0811-333333', 'position' => 'DevOps Engineer'],
        ];

        foreach ($projectUsers as $data) {
            $u = User::firstOrCreate(
                ['email' => $data['email']],
                array_merge($data, [
                    'password' => Hash::make('password'),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ])
            );
            if (!$u->hasRole('user')) {
                $u->assignRole('user');
            }
        }
    }
}
