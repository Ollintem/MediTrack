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
    Schema::create('personals', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
        $table->foreignId('clinica_id')->constrained('clinicas')->onDelete('cascade');
        $table->date('fecha_nacimiento')->nullable();
        $table->date('fecha_ingreso')->nullable();
        $table->string('rut', 20)->unique()->nullable();
        $table->string('nombre_completo', 150);
        $table->enum('genero', ['Masculino', 'Femenino', 'Otro'])->nullable();
        $table->string('telefono', 20)->nullable();
        $table->string('numero_registro', 50)->nullable();
        $table->string('direccion', 255)->nullable();
        $table->string('rut_profesional', 50)->nullable();
        $table->string('especialidad_principal', 100)->nullable();
        $table->enum('turno', ['Mañana', 'Tarde', 'Completo', 'Noche'])->default('Completo');
        $table->enum('estado', ['Activo', 'Inactivo'])->default('Activo');
        $table->string('foto_url', 500)->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personals');
    }
};
