
<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_cliente_manga_suscripciones_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('cliente_manga_suscripciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained()->onDelete('cascade');
            $table->foreignId('manga_id')->constrained()->onDelete('cascade');
            $table->string('fcm_token'); // Token de Firebase
            $table->timestamps();

            // Un cliente solo puede tener una suscripción por manga con el mismo token
            $table->unique(['cliente_id', 'manga_id', 'fcm_token']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('cliente_manga_suscripciones');
    }
};
