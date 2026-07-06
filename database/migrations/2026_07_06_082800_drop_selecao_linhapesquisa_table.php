<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropSelecaoLinhapesquisaTable extends Migration
{
    public function up()
    {
        Schema::dropIfExists('selecao_linhapesquisa');
    }

    public function down()
    {
        Schema::create('selecao_linhapesquisa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('selecao_id')->constrained('selecoes')->onDelete('cascade');
            $table->foreignId('linhapesquisa_id')->constrained('linhaspesquisa')->onDelete('cascade');
            $table->timestamps();
        });
    }
}
