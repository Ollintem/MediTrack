<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Costo unitario de cada movimiento de inventario (precio de compra en ese momento).
     * Lo usan los Reportes: entradas × costo = compras; salidas × costo = costo de lo vendido.
     * Si después cambia el precio de compra del producto, los movimientos anteriores conservan el suyo.
     */
    public function up(): void
    {
        Schema::table('movimientos_inventario', function (Blueprint $table) {
            $table->decimal('costo_unitario', 12, 2)->nullable()->after('cantidad');
        });

        // Movimientos anteriores: se toma el precio de compra actual del producto (mejor dato disponible)
        DB::table('movimientos_inventario')
            ->join('productos_inventario', 'productos_inventario.id', '=', 'movimientos_inventario.producto_id')
            ->whereNull('movimientos_inventario.costo_unitario')
            ->update(['movimientos_inventario.costo_unitario' => DB::raw('productos_inventario.precio_compra')]);
    }

    public function down(): void
    {
        Schema::table('movimientos_inventario', function (Blueprint $table) {
            $table->dropColumn('costo_unitario');
        });
    }
};
