<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ventas')) {
            Schema::create('ventas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cliente_id')->constrained()->onDelete('cascade');
                $table->decimal('total', 10, 2);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
