<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('recetas') && !Schema::hasColumn('recetas', 'consulta_id')) {
            Schema::table('recetas', function (Blueprint $table) {
                // Consulta de la que proviene la receta (opcional: las recetas manuales quedan en NULL)
                $table->foreignId('consulta_id')
                    ->nullable()
                    ->after('paciente_id')
                    ->constrained('consultas')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('recetas', 'consulta_id')) {
            Schema::table('recetas', function (Blueprint $table) {
                $table->dropConstrainedForeignId('consulta_id');
            });
        }
    }
};