<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orden_metadata', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cliente_id');
            $table->json('productos');
            $table->timestamps();

            // Si tenés tabla clientes, podés activar la FK
            // $table->foreign('cliente_id')
            //       ->references('id')
            //       ->on('clientes')
            //       ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orden_metadata');
    }
};
