<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('factura_detalle')) {
            Schema::create('factura_detalle', function (Blueprint $table) {
                $table->id();
                $table->foreignId('factura_id')->constrained('facturas')->onDelete('cascade');
                $table->foreignId('tomo_id')->constrained('tomos')->onDelete('cascade');
                $table->integer('cantidad');
                $table->decimal('precio_unitario', 8, 2);
                $table->decimal('subtotal', 10, 2);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('factura_detalle');
    }
};

