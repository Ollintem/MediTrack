<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Movimientos de inventario con trazabilidad completa:
     *  - user_id: quién hizo el movimiento (también administradores sin ficha de personal).
     *  - personal_id pasa a ser opcional por esa misma razón (sigue restringido al borrar).
     *  - ticket_id, referencia y lote: de qué venta o de qué factura/lote de proveedor viene.
     */
    public function up(): void
    {
        Schema::table('movimientos_inventario', function (Blueprint $table) {
            $table->dropForeign(['personal_id']);
        });

        Schema::table('movimientos_inventario', function (Blueprint $table) {
            $table->unsignedBigInteger('personal_id')->nullable()->change();
            $table->foreign('personal_id')
                  ->references('id')->on('personals')
                  ->restrictOnDelete();

            $table->foreignId('user_id')->nullable()->after('personal_id')
                  ->constrained('users')->nullOnDelete();
            $table->foreignId('ticket_id')->nullable()->after('user_id')
                  ->constrained('tickets_venta')->nullOnDelete();
            $table->string('referencia', 255)->nullable()->after('motivo');
            $table->string('lote', 100)->nullable()->after('referencia');
        });
    }

    public function down(): void
    {
        Schema::table('movimientos_inventario', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ticket_id');
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['referencia', 'lote']);
        });
        // personal_id se deja nullable: puede haber movimientos sin personal que impedirían volver a NOT NULL.
    }
};
