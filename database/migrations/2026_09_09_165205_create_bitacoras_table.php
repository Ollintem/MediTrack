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
        Schema::create('bitacoras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('usuario_nombre')->nullable(); // Respaldo por si el usuario es eliminado
            $table->string('modulo');                     // Ej: Personal, Roles, Pacientes
            $table->string('accion');                     // Ej: Crear, Editar, Eliminar, Login
            $table->text('descripcion');                  // Descripción legible de lo que ocurrió
            $table->json('detalles')->nullable();         // Guardar los datos anteriores/nuevos en formato JSON
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bitacoras');
    }
};
