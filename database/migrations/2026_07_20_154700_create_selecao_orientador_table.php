<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSelecaoOrientadorTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('selecao_orientador', function (Blueprint $table) {
            $table->id();
            $table->foreignId('selecao_id')->constrained('selecoes')->onDelete('cascade');
            $table->string('orientador_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('selecao_orientador');
    }
}
