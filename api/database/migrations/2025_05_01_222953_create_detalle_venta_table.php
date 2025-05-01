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
     // database/migrations/xxxx_xx_xx_create_detalle_venta_table.php
     Schema::create('detalle_venta', function (Blueprint $table) {
        $table->id();
        $table->foreignId('venta_id')->constrained('ventas')->onDelete('cascade');
        $table->foreignId('tomo_id')->constrained('tomos')->onDelete('cascade');
        $table->integer('cantidad');
        $table->decimal('precio_unitario', 8, 2);
        $table->timestamps();
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_venta');
    }
};
