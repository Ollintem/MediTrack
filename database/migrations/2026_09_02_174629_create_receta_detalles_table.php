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
       Schema::create('receta_detalles', function (Blueprint $table) {
        $table->id();
        $table->foreignId('receta_id')->constrained('recetas')->onDelete('cascade');
        $table->string('medicamento', 150);
        $table->string('dosis', 80);
        $table->string('frecuencia', 80);
        $table->string('duracion', 80)->nullable();
        $table->text('instrucciones')->nullable();
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receta_detalles');
    }
};
