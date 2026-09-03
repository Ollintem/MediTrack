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
        Schema::create('citas', function (Blueprint $table) {
        $table->id();
        $table->foreignId('paciente_id')->constrained('pacientes')->onDelete('cascade');
        $table->foreignId('personal_id')->constrained('personals')->onDelete('cascade');
        $table->foreignId('consultorio_id')->constrained('consultorios')->onDelete('cascade');
        $table->foreignId('clinica_id')->constrained('clinicas')->onDelete('cascade');
        $table->date('fecha');
        $table->time('hora');
        $table->integer('duracion_min')->default(30);
        $table->text('motivo')->nullable();
        $table->enum('tipo_consulta', ['Seguimiento', 'Primera Vez', 'Urgencia']);
        $table->enum('estado', ['Pendiente', 'Confirmada', 'En curso', 'Finalizada', 'Cancelada'])->default('Pendiente');
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('citas');
    }
};
