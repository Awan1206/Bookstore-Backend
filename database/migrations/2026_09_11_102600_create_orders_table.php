<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code')->unique(); // contoh: A021
            $table->foreignId('user_id')->constrained()->onDelete('restrict');
            $table->decimal('total_amount', 12, 2);
            // pending: baru checkout, menunggu ke kasir
            // confirmed: admin sudah konfirmasi pesanan
            // paid: sudah bayar cash & kembalian terhitung
            // completed: transaksi selesai
            // cancelled: dibatalkan
            $table->enum('status', ['pending', 'confirmed', 'paid', 'completed', 'cancelled'])
                  ->default('pending');
            $table->decimal('cash_received', 12, 2)->nullable();
            $table->decimal('change_amount', 12, 2)->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};