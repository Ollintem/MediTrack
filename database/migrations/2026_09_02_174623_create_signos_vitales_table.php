<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signos_vitales', function (Blueprint $table) {
            $table->id();
            // consulta_id es NULLABLE para permitir registrar signos desde recetas sin forzar una consulta previa
            $table->foreignId('consulta_id')->nullable()->constrained('consultas')->onDelete('cascade');
            $table->foreignId('paciente_id')->constrained('pacientes')->onDelete('cascade');
            $table->dateTime('fecha_registro');
            $table->integer('nivel_dolor')->nullable();
            $table->text('notas')->nullable();
            $table->decimal('pa_sistolica', 5, 1)->nullable();
            $table->decimal('pa_diastolica', 5, 1)->nullable();
            $table->decimal('frecuencia_cardiaca', 5, 1)->nullable();
            $table->decimal('frecuencia_respiratoria', 5, 1)->nullable();
            $table->decimal('temperatura', 4, 1)->nullable();
            $table->decimal('saturacion_oxigeno', 5, 1)->nullable();
            $table->decimal('peso_kg', 5, 1)->nullable();
            $table->decimal('talla_cm', 5, 1)->nullable();
            $table->decimal('imc', 4, 1)->nullable();
            $table->decimal('glucosa_sangre', 6, 1)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signos_vitales');
    }
};