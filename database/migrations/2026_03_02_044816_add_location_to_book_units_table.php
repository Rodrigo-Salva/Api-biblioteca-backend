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
        Schema::table('book_units', function (Blueprint $table) {
            $table->string('aisle')->nullable(); // Pasillo
            $table->string('shelf')->nullable(); // Estante
            $table->string('position')->nullable(); // Posición/Nivel
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('book_units', function (Blueprint $table) {
            $table->dropColumn(['aisle', 'shelf', 'position']);
        });
    }
};
