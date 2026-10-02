<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pagos individuales: cada recibo de nómina se paga por separado
 * (fecha, forma de pago y referencia propias de cada empleado).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recibos_nomina', function (Blueprint $table) {
            if (!Schema::hasColumn('recibos_nomina', 'estado')) {
                $table->string('estado', 20)->default('Pendiente')->after('neto');   // Pendiente, Pagado
            }
            if (!Schema::hasColumn('recibos_nomina', 'fecha_pago')) {
                $table->date('fecha_pago')->nullable()->after('estado');
            }
            if (!Schema::hasColumn('recibos_nomina', 'metodo_pago')) {
                $table->string('metodo_pago', 20)->nullable()->after('fecha_pago');  // EFECTIVO, TRANSFERENCIA, CHEQUE
            }
            if (!Schema::hasColumn('recibos_nomina', 'referencia')) {
                $table->string('referencia', 100)->nullable()->after('metodo_pago');
            }
            if (!Schema::hasColumn('recibos_nomina', 'pagado_por')) {
                $table->foreignId('pagado_por')->nullable()->after('referencia')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('recibos_nomina', function (Blueprint $table) {
            if (Schema::hasColumn('recibos_nomina', 'pagado_por')) {
                $table->dropConstrainedForeignId('pagado_por');
            }
            foreach (['referencia', 'metodo_pago', 'fecha_pago', 'estado'] as $col) {
                if (Schema::hasColumn('recibos_nomina', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
