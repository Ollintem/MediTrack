<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Horas extra por cuota declarada:
     *  - personals.cuota_hora_extra: lo que se paga por cada hora extra a ese empleado (se declara en su ficha).
     *  - recibo_extras: pagos extraordinarios de un recibo, cada uno con su concepto y sus horas.
     *    El monto = horas × cuota (la cuota se guarda en el renglón para que el recibo no cambie
     *    si después se modifica la cuota del empleado).
     *  - recibos_nomina.horas_extra sigue existiendo: ahora es la suma de esos renglones.
     */
    public function up(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            if (!Schema::hasColumn('personals', 'cuota_hora_extra')) {
                $table->decimal('cuota_hora_extra', 10, 2)->default(0)->after('salario_diario');
            }
        });

        Schema::create('recibo_extras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recibo_id')->constrained('recibos_nomina')->cascadeOnDelete();
            $table->string('concepto', 150);
            $table->decimal('horas', 6, 2);
            $table->decimal('cuota', 10, 2);
            $table->decimal('monto', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recibo_extras');

        Schema::table('personals', function (Blueprint $table) {
            if (Schema::hasColumn('personals', 'cuota_hora_extra')) {
                $table->dropColumn('cuota_hora_extra');
            }
        });
    }
};
