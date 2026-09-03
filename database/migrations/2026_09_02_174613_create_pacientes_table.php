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
        Schema::create('pacientes', function (Blueprint $table) {
        $table->id();
        $table->foreignId('clinica_id')->constrained('clinicas')->onDelete('cascade');
        $table->foreignId('medico_tratante_id')->nullable()->constrained('personals')->onDelete('set null');
        $table->string('codigo', 20)->unique()->nullable();
        $table->string('nombre_completo', 150);
        $table->string('rut', 20)->unique()->nullable();
        $table->date('fecha_nacimiento')->nullable();
        $table->enum('genero', ['Masculino', 'Femenino', 'Otro'])->nullable();
        $table->string('estado_civil', 30)->nullable();
        $table->string('nacionalidad', 50)->nullable();
        $table->string('grupo_sanguineo', 10)->nullable();
        $table->string('telefono', 20)->nullable();
        $table->string('celular', 20)->nullable();
        $table->string('email', 150)->nullable();
        $table->string('direccion', 255)->nullable();
        $table->string('contacto_emerg_nombre', 150)->nullable();
        $table->string('contacto_emerg_relacion', 50)->nullable();
        $table->string('contacto_emerg_telefono', 20)->nullable();
        $table->string('aseguradora', 100)->nullable();
        $table->string('numero_poliza', 50)->nullable();
        $table->string('vigencia_poliza', 30)->nullable();
        $table->boolean('acepta_aviso_privacidad')->default(false);
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
        Schema::dropIfExists('pacientes');
    }
};
