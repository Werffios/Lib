<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('movements', function (Blueprint $table) {
            $table->id();
            $table->enum('movement_type', ['in', 'out', 'return', 'adjustment']);
            $table->integer('quantity');
            $table->string('reference_type')->nullable(); // 'sale', 'purchase', 'return', etc.
            $table->unsignedBigInteger('reference_id')->nullable(); // ID de la venta, compra, etc.
            $table->text('notes')->nullable();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movements');
    }
};
