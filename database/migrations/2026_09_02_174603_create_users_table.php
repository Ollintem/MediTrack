<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            
            // Permitir valores nulos para el registro inicial
            $table->foreignId('clinica_id')->nullable()->constrained('clinicas')->onDelete('set null');
            $table->foreignId('rol_id')->nullable()->constrained('roles')->onDelete('set null');
            
            $table->string('nombre')->nullable();
            $table->string('apellido')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('estado', ['Activo', 'Inactivo'])->default('Activo');
            $table->timestamp('ultimo_login')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};