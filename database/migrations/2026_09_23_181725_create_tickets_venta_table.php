<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets_venta', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_ticket', 50)->unique(); // Ej: TCK-84920
            $table->foreignId('paciente_id')->nullable()->constrained('pacientes')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // Usuario que genera el ticket
            $table->decimal('monto_total', 12, 2)->default(0);
            $table->enum('status', ['pendiente', 'pagado', 'entregado', 'cancelado', 'expirado'])->default('pendiente');
            $table->timestamp('expires_at')->comment('Hora de creación + 25 minutos para controlar la liberación automática del apartado');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets_venta');
    }
};