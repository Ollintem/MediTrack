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
       Schema::create('recetas', function (Blueprint $table) {
        $table->id();
        $table->foreignId('consulta_id')->constrained('consultas')->onDelete('cascade');
        $table->foreignId('paciente_id')->constrained('pacientes')->onDelete('cascade');
        $table->foreignId('personal_id')->constrained('personals')->onDelete('cascade');
        $table->dateTime('fecha');
        $table->text('notas')->nullable();
        $table->enum('estado', ['Borrador', 'Emitida', 'Impresa'])->default('Emitida');
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recetas');
    }
};
