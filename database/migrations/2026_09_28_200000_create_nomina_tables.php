<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Salario del empleado (se usa para generar la nómina automáticamente)
        Schema::table('personals', function (Blueprint $table) {
            if (!Schema::hasColumn('personals', 'salario_diario')) {
                $table->decimal('salario_diario', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('personals', 'tipo_pago')) {
                $table->string('tipo_pago', 20)->default('QUINCENAL'); // SEMANAL, QUINCENAL, MENSUAL
            }
        });

        // 2. Periodos de nómina (cada semana, quincena o mes que se paga)
        Schema::create('periodos_nomina', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 20);                        // SEMANAL, QUINCENAL, MENSUAL
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->date('fecha_pago')->nullable();
            $table->string('estado', 20)->default('Abierto');  // Abierto, Cerrado, Pagado
            $table->text('notas')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 3. Recibos: lo que se le paga a cada empleado en un periodo
        Schema::create('recibos_nomina', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodo_id')->constrained('periodos_nomina')->cascadeOnDelete();
            $table->foreignId('personal_id')->constrained('personals')->cascadeOnDelete();

            // Percepciones
            $table->decimal('salario_diario', 12, 2)->default(0);
            $table->decimal('dias_trabajados', 5, 2)->default(0);
            $table->decimal('sueldo_base', 12, 2)->default(0);
            $table->decimal('horas_extra', 12, 2)->default(0);
            $table->decimal('bonos', 12, 2)->default(0);

            // Deducciones (montos capturados manualmente)
            $table->decimal('isr', 12, 2)->default(0);
            $table->decimal('imss', 12, 2)->default(0);
            $table->decimal('otras_deducciones', 12, 2)->default(0);

            // Totales
            $table->decimal('total_percepciones', 12, 2)->default(0);
            $table->decimal('total_deducciones', 12, 2)->default(0);
            $table->decimal('neto', 12, 2)->default(0);

            $table->text('notas')->nullable();
            $table->timestamps();

            $table->unique(['periodo_id', 'personal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recibos_nomina');
        Schema::dropIfExists('periodos_nomina');

        Schema::table('personals', function (Blueprint $table) {
            if (Schema::hasColumn('personals', 'tipo_pago')) {
                $table->dropColumn('tipo_pago');
            }
            if (Schema::hasColumn('personals', 'salario_diario')) {
                $table->dropColumn('salario_diario');
            }
        });
    }
};