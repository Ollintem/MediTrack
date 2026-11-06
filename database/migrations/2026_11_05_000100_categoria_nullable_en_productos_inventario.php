<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Al eliminar una categoría, sus productos quedan "sin categoría"
     * (antes se borraban en cascada junto con sus renglones de tickets).
     */
    public function up(): void
    {
        Schema::table('productos_inventario', function (Blueprint $table) {
            $table->dropForeign(['categoria_id']);
        });

        Schema::table('productos_inventario', function (Blueprint $table) {
            $table->unsignedBigInteger('categoria_id')->nullable()->change();
            $table->foreign('categoria_id')
                  ->references('id')->on('categorias_inventario')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('productos_inventario', function (Blueprint $table) {
            $table->dropForeign(['categoria_id']);
        });

        // Solo se puede volver a NOT NULL si no hay productos sin categoría.
        $hayNulos = DB::table('productos_inventario')->whereNull('categoria_id')->exists();

        Schema::table('productos_inventario', function (Blueprint $table) use ($hayNulos) {
            if (! $hayNulos) {
                $table->unsignedBigInteger('categoria_id')->nullable(false)->change();
            }
            $table->foreign('categoria_id')
                  ->references('id')->on('categorias_inventario')
                  ->onDelete('cascade');
        });
    }
};
