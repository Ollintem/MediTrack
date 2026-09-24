<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_caja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->nullable()->constrained('tickets_venta')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // Cajero
            $table->enum('tipo', ['Ingreso', 'Egreso']);
            $table->string('concepto', 255);
            $table->decimal('monto', 12, 2);
            $table->enum('metodo_pago', ['Efectivo', 'Tarjeta', 'Transferencia'])->default('Efectivo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_caja');
    }
};