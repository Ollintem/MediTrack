<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un consultorio con citas ya no se puede borrar (antes se borraban
     * sus citas en cascada). ConsultorioController::destroy responde 409.
     */
    public function up(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dropForeign(['consultorio_id']);
            $table->foreign('consultorio_id')
                  ->references('id')->on('consultorios')
                  ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dropForeign(['consultorio_id']);
            $table->foreign('consultorio_id')
                  ->references('id')->on('consultorios')
                  ->onDelete('cascade');
        });
    }
};
