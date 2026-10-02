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
       Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('razon_social');
            $table->string('nombre_comercial')->nullable();
            $table->string('rut_rfc')->nullable();
            $table->string('categoria'); // Ej: Medicamentos, Insumos Médicos, Equipamiento, Servicios, Otros
            
            // Datos de Contacto
            $table->string('contacto_nombre')->nullable();
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->text('direccion')->nullable();

            // Condiciones Comerciales y Datos Bancarios
            $table->integer('dias_credito')->default(0); // 0 = Contado
            $table->string('banco')->nullable();
            $table->string('cuenta_bancaria')->nullable();

            // Estado y Notas
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->text('notas')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proveedors');
    }
};
