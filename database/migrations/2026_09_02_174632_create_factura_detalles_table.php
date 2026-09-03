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
       Schema::create('factura_detalles', function (Blueprint $table) {
        $table->id();
        $table->foreignId('factura_id')->constrained('facturas')->onDelete('cascade');
        $table->string('descripcion', 255);
        $table->integer('cantidad')->default(1);
        $table->decimal('precio_unitario', 12, 2);
        $table->decimal('subtotal', 12, 2);
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('factura_detalles');
    }
};
