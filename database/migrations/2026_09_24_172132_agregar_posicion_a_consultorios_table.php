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
    Schema::table('consultorios', function (Blueprint $table) {
        $table->unsignedSmallInteger('pos_x')->nullable();
        $table->unsignedSmallInteger('pos_y')->nullable();
    });
}

public function down(): void
{
    Schema::table('consultorios', function (Blueprint $table) {
        $table->dropColumn(['pos_x', 'pos_y']);
    });
}
};
