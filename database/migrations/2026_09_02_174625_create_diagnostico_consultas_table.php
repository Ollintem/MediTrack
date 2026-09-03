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
        Schema::create('diagnostico_consultas', function (Blueprint $table) {
        $table->id();
        $table->foreignId('consulta_id')->constrained('consultas')->onDelete('cascade');
        $table->string('codigo_cie10', 10)->nullable();
        $table->string('descripcion', 255);
        $table->enum('tipo', ['Principal', 'Secundario'])->default('Principal');
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diagnostico_consultas');
    }
};
