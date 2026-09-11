<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Membuat 2 akun contoh untuk testing: 1 admin, 1 user/buyer biasa.
     * WAJIB dijalankan SETELAH RoleSeeder, karena butuh role_id.
     */
    public function run(): void
    {
        $adminRole = Role::where('name', 'admin')->first();
        $userRole = Role::where('name', 'user')->first();

        // ---------- Akun Admin ----------
        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Admin Toko',
                'email' => 'admin@bookstore.test',
                'phone' => '081234567890',
                'password' => 'admin123', // ganti setelah testing selesai!
                'role_id' => $adminRole?->id,
            ]
        );
        if ($adminRole && $admin->role_id !== $adminRole->id) {
            $admin->update(['role_id' => $adminRole->id]);
        }

        // ---------- Akun User/Buyer ----------
        $user = User::firstOrCreate(
            ['username' => 'user1'],
            [
                'name' => 'Budi Pembeli',
                'email' => 'user1@bookstore.test',
                'phone' => '081298765432',
                'password' => 'user123', // ganti setelah testing selesai!
                'role_id' => $userRole?->id,
            ]
        );
        if ($userRole && $user->role_id !== $userRole->id) {
            $user->update(['role_id' => $userRole->id]);
        }

        $this->command->info('Akun testing dibuat:');
        $this->command->info('  Admin -> username: admin   | password: admin123');
        $this->command->info('  User  -> username: user1   | password: user123');
    }
}