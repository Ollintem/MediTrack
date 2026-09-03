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
        Schema::create('archivos_clinicos', function (Blueprint $table) {
        $table->id();
        $table->foreignId('paciente_id')->constrained('pacientes')->onDelete('cascade');
        $table->foreignId('consulta_id')->nullable()->constrained('consultas')->onDelete('set null');
        $table->foreignId('personal_id')->nullable()->constrained('personals')->onDelete('set null');
        $table->dateTime('fecha_carga');
        $table->string('tipo', 80);
        $table->string('nombre_archivo', 255);
        $table->string('url', 500);
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archivos_clinicos');
    }
};
