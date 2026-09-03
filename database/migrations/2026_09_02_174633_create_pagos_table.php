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
        Schema::create('pagos', function (Blueprint $table) {
        $table->id();
        $table->foreignId('factura_id')->constrained('facturas')->onDelete('cascade');
        $table->dateTime('fecha_pago');
        $table->decimal('monto', 12, 2);
        $table->string('metodo', 100)->nullable();
        $table->string('referencia', 100)->nullable();
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
