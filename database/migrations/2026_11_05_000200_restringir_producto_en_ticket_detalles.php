<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un producto con ventas registradas ya no se puede borrar:
     * antes se eliminaban en cascada sus renglones de tickets cobrados.
     */
    public function up(): void
    {
        Schema::table('ticket_detalles', function (Blueprint $table) {
            $table->dropForeign(['producto_id']);
            $table->foreign('producto_id')
                  ->references('id')->on('productos_inventario')
                  ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ticket_detalles', function (Blueprint $table) {
            $table->dropForeign(['producto_id']);
            $table->foreign('producto_id')
                  ->references('id')->on('productos_inventario')
                  ->onDelete('cascade');
        });
    }
};
