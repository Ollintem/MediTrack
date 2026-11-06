<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Una cita tiene como máximo una consulta, y una consulta como máximo una receta.
     * El código ya lo revisaba, pero dos clics rápidos podían crear duplicados.
     * (Varias filas con NULL siguen permitidas: consultas sin cita y recetas manuales.)
     */
    public function up(): void
    {
        $this->sinDuplicados('consultas', 'cita_id');
        $this->sinDuplicados('recetas', 'consulta_id');

        Schema::table('consultas', function (Blueprint $table) {
            $table->unique('cita_id');
        });

        Schema::table('recetas', function (Blueprint $table) {
            $table->unique('consulta_id');
        });
    }

    public function down(): void
    {
        // Las llaves foráneas conservan su propio índice (creado con la FK), así que se puede quitar el único.
        Schema::table('consultas', function (Blueprint $table) {
            $table->dropUnique(['cita_id']);
        });

        Schema::table('recetas', function (Blueprint $table) {
            $table->dropUnique(['consulta_id']);
        });
    }

    /** Detiene la migración con un mensaje claro si ya hay duplicados que limpiar a mano. */
    private function sinDuplicados(string $tabla, string $columna): void
    {
        $repetidos = DB::table($tabla)
            ->whereNotNull($columna)
            ->groupBy($columna)
            ->havingRaw('COUNT(*) > 1')
            ->pluck($columna);

        if ($repetidos->isNotEmpty()) {
            throw new RuntimeException(
                "La tabla {$tabla} tiene registros repetidos en {$columna} (" . $repetidos->implode(', ') . '). '
                . 'Deja solo uno por cada valor y vuelve a ejecutar las migraciones.'
            );
        }
    }
};
