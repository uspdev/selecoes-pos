<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropOrientadoresTables extends Migration
{
    public function up()
    {
        Schema::dropIfExists('selecao_orientador');
        Schema::dropIfExists('linhapesquisa_orientador');
        Schema::dropIfExists('orientadores');
    }

    public function down()
    {
        Schema::create('orientadores', function (Blueprint $table) {
            $table->id();
            $table->string('codpes');
            $table->string('nome')->nullable();
            $table->string('email')->nullable();
            $table->boolean('externo')->default(0);
            $table->timestamps();
        });
        Schema::create('linhapesquisa_orientador', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('linhapesquisa_id');
            $table->unsignedBigInteger('orientador_id');
            $table->timestamps();
            $table->foreign('linhapesquisa_id')->references('id')->on('linhaspesquisa')->onDelete('cascade');
        });
        Schema::create('selecao_orientador', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('selecao_id');
            $table->unsignedBigInteger('orientador_id');
            $table->timestamps();
            $table->foreign('selecao_id')->references('id')->on('selecoes')->onDelete('cascade');
        });
    }
}
