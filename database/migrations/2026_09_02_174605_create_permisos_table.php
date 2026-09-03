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
    Schema::create('permisos', function (Blueprint $table) {
        $table->id();
        $table->foreignId('rol_id')->constrained('roles')->onDelete('cascade');
        $table->foreignId('modulo_id')->constrained('modulos')->onDelete('cascade');
        $table->boolean('puede_ver')->default(false);
        $table->boolean('puede_crear')->default(false);
        $table->boolean('puede_editar')->default(false);
        $table->boolean('puede_eliminar')->default(false);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permisos');
    }
};
