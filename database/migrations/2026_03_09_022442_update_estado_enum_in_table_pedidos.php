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
        Schema::table('pedidos', function (Blueprint $table)  {

            DB::statement("ALTER TABLE pedidos MODIFY estado ENUM(
                'pendiente',
                'pagada',
                'cancelada',
                'entregada') NOT NULL DEFAULT 'pendiente'");

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('table_pedidos', function (Blueprint $table) {
            //
        });
    }
};
