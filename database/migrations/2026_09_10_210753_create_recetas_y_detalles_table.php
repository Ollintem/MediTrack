<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla principal de Recetas
        if (!Schema::hasTable('recetas')) {
            Schema::create('recetas', function (Blueprint $table) {
                $table->id();
                $table->string('folio')->unique();
                $table->foreignId('paciente_id')->constrained('pacientes')->onDelete('cascade');
                $table->foreignId('personal_id')->nullable()->constrained('personals')->onDelete('set null');
                $table->date('fecha_emision');
                $table->text('indicaciones_generales')->nullable();
                $table->timestamps();
            });
        }

        // Tabla de Detalles de Medicamentos
        if (!Schema::hasTable('receta_detalles')) {
            Schema::create('receta_detalles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('receta_id')->constrained('recetas')->onDelete('cascade');
                $table->string('medicamento');
                $table->string('dosis')->nullable();
                $table->string('frecuencia')->nullable();
                $table->string('duracion')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('receta_detalles');
        Schema::dropIfExists('recetas');
    }
};