<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El módulo de Facturación no se va a usar: se quitan su módulo de permisos
     * y sus tablas (facturas, factura_detalles, pagos), que nunca tuvieron datos.
     * Si alguna tuviera registros, la migración se detiene para no perder información.
     */
    public function up(): void
    {
        foreach (['pagos', 'factura_detalles', 'facturas'] as $tabla) {
            if (Schema::hasTable($tabla) && DB::table($tabla)->exists()) {
                throw new RuntimeException(
                    "La tabla {$tabla} tiene registros. Respáldalos o bórralos antes de quitar Facturación."
                );
            }
        }

        // Al borrar el módulo se borran en cascada sus permisos en todos los roles
        DB::table('modulos')->where('nombre', 'Facturación')->delete();

        Schema::dropIfExists('pagos');
        Schema::dropIfExists('factura_detalles');
        Schema::dropIfExists('facturas');
    }

    /** Recrea las tablas tal como estaban (vacías) y el módulo de permisos. */
    public function down(): void
    {
        Schema::create('facturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignId('clinica_id')->constrained('clinicas')->onDelete('cascade');
            $table->string('numero', 50)->unique();
            $table->date('fecha_emision');
            $table->decimal('monto_total', 12, 2);
            $table->string('metodo_pago', 50)->nullable();
            $table->enum('estado', ['Pendiente', 'Pagada', 'Vencida', 'Cancelada'])->default('Pendiente');
            $table->timestamps();
        });

        Schema::create('factura_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factura_id')->constrained('facturas')->onDelete('cascade');
            $table->string('descripcion', 255);
            $table->integer('cantidad')->default(1);
            $table->decimal('precio_unitario', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });

        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factura_id')->constrained('facturas')->onDelete('cascade');
            $table->dateTime('fecha_pago');
            $table->decimal('monto', 12, 2);
            $table->string('metodo', 100)->nullable();
            $table->string('referencia', 100)->nullable();
            $table->timestamps();
        });

        if (!DB::table('modulos')->where('nombre', 'Facturación')->exists()) {
            DB::table('modulos')->insert(['nombre' => 'Facturación', 'created_at' => now(), 'updated_at' => now()]);
        }
    }
};
