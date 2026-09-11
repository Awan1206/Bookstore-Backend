<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->onDelete('restrict');
            $table->string('title')->unique(); // anti-duplikat di level DB
            $table->date('publish_date')->nullable();
            $table->unsignedSmallInteger('publish_year')->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->decimal('cost_price', 12, 2); // harga modal
            $table->decimal('sell_price', 12, 2); // harga jual
            $table->text('description')->nullable();
            $table->string('image')->nullable(); // path di storage/app/public
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};