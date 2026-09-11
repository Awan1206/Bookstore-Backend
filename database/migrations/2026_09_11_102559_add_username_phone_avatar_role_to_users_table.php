<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // PENTING: migration ini harus dijalankan SETELAH tabel roles dibuat,
    // karena kolom role_id di sini punya foreign key ke roles.id
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->after('name');
            $table->string('phone')->unique()->nullable()->after('email');
            $table->string('avatar')->nullable()->after('password');
            $table->foreignId('role_id')->nullable()->after('avatar')
                  ->constrained('roles')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn(['username', 'phone', 'avatar', 'role_id']);
        });
    }
};