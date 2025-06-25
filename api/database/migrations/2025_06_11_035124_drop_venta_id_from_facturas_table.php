<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropVentaIdFromFacturasTable extends Migration
{
    public function up(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            // Primero suelta la FK
            $table->dropForeign(['venta_id']);
            // Luego la columna
            $table->dropColumn('venta_id');
        });
    }

    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            // Reconstruye la columna y la FK
            $table->foreignId('venta_id')
                  ->after('id')
                  ->constrained('ventas')
                  ->onDelete('cascade');
        });
    }
}
