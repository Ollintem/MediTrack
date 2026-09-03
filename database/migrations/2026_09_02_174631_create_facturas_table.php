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
        Schema::create('facturas', function (Blueprint $table) {
        $table->id();
        $table->foreignId('paciente_id')->constrained('pacientes')->onDelete('cascade');
        $table->foreignId('clinica_id')->constrained('clinicas')->onDelete('cascade');
        $table->string('numero', 50)->unique();
        $table->date('fecha_emision');
        $table->decimal('monto_total', 12, 2);
        $table->string('metodo_pago', 50)->nullable();
        $table->enum('estado', ['Pendiente', 'Pagada', 'Vencida', 'Cancelada'])->default('Pendiente');
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facturas');
    }
};
