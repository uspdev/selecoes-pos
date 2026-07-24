<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateFuncoesTableRenameValues extends Migration
{
    public function up()
    {
        DB::table('user_programa')->where('funcao', 'Coordenadores do Programa')->update(['funcao' => 'Coordenadores(as) do Programa']);
        DB::table('user_programa')->where('funcao', 'Coordenadores da Pós-Graduação')->update(['funcao' => 'Coordenadores(as) da Pós-Graduação']);
    }

    public function down()
    {
        DB::table('user_programa')->where('funcao', 'Coordenadores(as) do Programa')->update(['funcao' => 'Coordenadores do Programa']);
        DB::table('user_programa')->where('funcao', 'Coordenadores(as) da Pós-Graduação')->update(['funcao' => 'Coordenadores da Pós-Graduação']);
    }
}
