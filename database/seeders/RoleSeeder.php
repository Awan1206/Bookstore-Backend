<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'user']);

        $permissions = [
            ['name' => 'manage-categories', 'label' => 'Kelola Kategori (Tambah/Edit/Hapus)'],
            ['name' => 'manage-books', 'label' => 'Kelola Buku (Tambah/Edit/Hapus/Upload Gambar/Atur Stok & Harga)'],
            ['name' => 'manage-users', 'label' => 'Manajemen User'],
            ['name' => 'confirm-orders', 'label' => 'Lihat Semua Pesanan, Konfirmasi, Kasir & Hitung Kembalian'],
            ['name' => 'view-reports', 'label' => 'Laporan Penjualan & Download Laporan'],
            ['name' => 'reply-chat', 'label' => 'Kelola Percakapan Live Chat (sisi Admin)'],
        ];

        foreach ($permissions as $perm) {
            $permission = Permission::firstOrCreate(['name' => $perm['name']], $perm);
            $adminRole->permissions()->syncWithoutDetaching($permission->id);
        }

        // role "user" sengaja tidak dikasih permission admin apa pun
    }
}