<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cajas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Fondo de efectivo
            $table->decimal('monto_inicial', 12, 2)->default(0.00);
            $table->decimal('monto_final', 12, 2)->nullable();

            // Totales al cierre del turno
            $table->decimal('total_efectivo', 12, 2)->default(0.00);
            $table->decimal('total_tarjeta', 12, 2)->default(0.00);
            $table->decimal('total_transferencia', 12, 2)->default(0.00);
            $table->decimal('total_ventas', 12, 2)->default(0.00);

            $table->enum('status', ['abierta', 'cerrada'])->default('abierta');
            $table->text('observaciones')->nullable();
            $table->text('observaciones_cierre')->nullable();
            $table->timestamp('fecha_apertura')->useCurrent();
            $table->timestamp('fecha_cierre')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cajas');
    }
};