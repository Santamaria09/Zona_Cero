<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            DB::statement("ALTER TABLE pagos MODIFY estado ENUM(
                'pendiente',
                'pagado',
                'rechazado') NOT NULL DEFAULT 'pendiente'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            // Revert to the previous enum values if needed.
            DB::statement("ALTER TABLE pagos MODIFY estado ENUM(
                'pendiente',
                'completado',
                'rechazado') NOT NULL DEFAULT 'pendiente'");
        });
    }
};
