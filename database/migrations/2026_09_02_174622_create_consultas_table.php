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
        Schema::create('consultas', function (Blueprint $table) {
        $table->id();
        $table->foreignId('cita_id')->nullable()->constrained('citas')->onDelete('set null');
        $table->foreignId('paciente_id')->constrained('pacientes')->onDelete('cascade');
        $table->foreignId('personal_id')->constrained('personals')->onDelete('cascade');
        $table->dateTime('fecha');
        $table->text('exploracion_fisica')->nullable();
        $table->text('cabeza_cuello')->nullable();
        $table->text('torax')->nullable();
        $table->text('abdomen')->nullable();
        $table->text('extremidades')->nullable();
        $table->text('piel_faneras')->nullable();
        $table->text('neurologico')->nullable();
        $table->text('notas_generales')->nullable();
        $table->enum('estado', ['En curso', 'Finalizada', 'Borrador'])->default('En curso');
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultas');
    }
};
