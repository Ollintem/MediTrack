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
        Schema::create('notificaciones', function (Blueprint $table) {
        $table->id();
        $table->foreignId('clinica_id')->constrained('clinicas')->onDelete('cascade');
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
        $table->string('titulo', 255);
        $table->text('mensaje');
        $table->boolean('leida')->default(false);
        $table->dateTime('fecha');
        $table->unsignedBigInteger('referencia_id')->nullable();
        $table->enum('tipo', ['Citas', 'Inventario', 'Facturación', 'Sistema']);
        $table->string('referencia_tipo', 50)->nullable();
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
