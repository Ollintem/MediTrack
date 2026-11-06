<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un miembro del personal con historial ya no se puede borrar
     * (antes se borraban en cascada sus citas, consultas, nómina y
     * movimientos). Se debe pasar a estado "Inactivo".
     */
    private array $tablas = ['citas', 'consultas', 'recibos_nomina', 'movimientos_inventario'];

    public function up(): void
    {
        foreach ($this->tablas as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropForeign(['personal_id']);
                $table->foreign('personal_id')
                      ->references('id')->on('personals')
                      ->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tablas as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropForeign(['personal_id']);
                $table->foreign('personal_id')
                      ->references('id')->on('personals')
                      ->onDelete('cascade');
            });
        }
    }
};
