<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La tabla `notificaciones` existía pero nunca se usó. Para ponerla en marcha:
     *  - tipo pasa de enum (Citas, Inventario, Facturación, Sistema) a texto: ahora también hay Caja y Nómina.
     *  - url: a dónde lleva el aviso al hacer clic.
     *  - índice por usuario y estado de lectura (la campana consulta esto cada 20 s).
     */
    public function up(): void
    {
        Schema::table('notificaciones', function (Blueprint $table) {
            $table->string('tipo', 30)->default('Sistema')->change();
            $table->string('url', 500)->nullable()->after('referencia_tipo');
            $table->index(['user_id', 'leida']);
        });
    }

    public function down(): void
    {
        // Antes de volver al enum, los tipos nuevos se pasan a "Sistema"
        DB::table('notificaciones')->whereNotIn('tipo', ['Citas', 'Inventario', 'Facturación', 'Sistema'])->update(['tipo' => 'Sistema']);

        Schema::table('notificaciones', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'leida']);
            $table->dropColumn('url');
            $table->enum('tipo', ['Citas', 'Inventario', 'Facturación', 'Sistema'])->change();
        });
    }
};
