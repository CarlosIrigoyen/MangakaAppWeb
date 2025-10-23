<?php
// database/migrations/xxxx_xx_xx_xxxxxx_drop_payment_fields_from_facturas_table.php

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
        Schema::table('facturas', function (Blueprint $table) {
            // Eliminar los campos que agregamos
            $table->dropColumn([
                'metodo_pago',
                'external_reference',
                'fecha_pago',
                'payment_id'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            // Recuperar los campos en caso de rollback
            $table->string('metodo_pago')->default('mercadopago')->after('pagado');
            $table->string('external_reference')->unique()->nullable()->after('metodo_pago');
            $table->timestamp('fecha_pago')->nullable()->after('external_reference');
            $table->string('payment_id')->nullable()->after('fecha_pago');
        });
    }
};
