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
        Schema::create('book_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->onDelete('cascade');
            $table->string('sku')->unique(); // ID único para cada copia
            $table->enum('condition', ['nuevo', 'bueno', 'regular', 'malo'])->default('nuevo');
            $table->enum('status', ['disponible', 'prestado', 'mantenimiento', 'perdido'])->default('disponible');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('book_units');
    }
};
