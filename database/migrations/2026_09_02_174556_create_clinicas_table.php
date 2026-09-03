<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('clinicas', function (Blueprint $table) {
        $table->id();
        $table->string('nombre', 150);
        $table->string('rut_empresa', 20)->unique();
        $table->string('direccion', 255);
        $table->string('telefono', 20);
        $table->boolean('confirmacion_auto_citas')->default(true);
        $table->boolean('recordatorio_pago_email')->default(false);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinicas');
    }
};
