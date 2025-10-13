<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('carritos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            $table->foreignId('tomo_id')->constrained('tomos')->onDelete('cascade');
            $table->integer('cantidad');
            $table->timestamps();

            $table->unique(['cliente_id', 'tomo_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('carritos');
    }
};
