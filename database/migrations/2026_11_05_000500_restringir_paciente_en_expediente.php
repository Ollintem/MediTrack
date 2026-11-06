<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un paciente con expediente ya no se puede borrar (NOM-004):
     * antes se borraban en cascada citas, consultas, signos, archivos,
     * recetas y facturas. Los antecedentes siguen en cascada.
     */
    private array $tablas = ['citas', 'consultas', 'signos_vitales', 'archivos_clinicos', 'recetas', 'facturas'];

    public function up(): void
    {
        foreach ($this->tablas as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropForeign(['paciente_id']);
                $table->foreign('paciente_id')
                      ->references('id')->on('pacientes')
                      ->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tablas as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropForeign(['paciente_id']);
                $table->foreign('paciente_id')
                      ->references('id')->on('pacientes')
                      ->onDelete('cascade');
            });
        }
    }
};
