<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega el correo de contacto de la clínica (usado en Configuración).
     */
    public function up(): void
    {
        if (! Schema::hasColumn('clinicas', 'email')) {
            Schema::table('clinicas', function (Blueprint $table) {
                $table->string('email', 150)->nullable()->after('telefono');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('clinicas', 'email')) {
            Schema::table('clinicas', function (Blueprint $table) {
                $table->dropColumn('email');
            });
        }
    }
};
