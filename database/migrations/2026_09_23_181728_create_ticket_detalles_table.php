<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets_venta')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('productos_inventario')->onDelete('cascade');
            $table->integer('cantidad');
            $table->decimal('precio_unitario', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_detalles');
    }
};