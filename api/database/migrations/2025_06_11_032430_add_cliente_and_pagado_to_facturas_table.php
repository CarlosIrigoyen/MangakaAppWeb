<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClienteAndPagadoToFacturasTable extends Migration
{
    public function up(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->foreignId('cliente_id')
                  ->after('venta_id')
                  ->constrained('clientes')
                  ->onDelete('cascade');
            $table->boolean('pagado')
                  ->after('numero')
                  ->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
            $table->dropColumn(['cliente_id', 'pagado']);
        });
    }
}
