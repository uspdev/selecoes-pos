<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateProgramasTableUpdateCampos extends Migration
{
    public function up()
    {
        Schema::table('programas', function (Blueprint $table) {
            $table->renameColumn('matricula', 'processos');
        });

        Schema::table('programas', function (Blueprint $table) {
            $table->string('processos')->change();
        });

        DB::table('programas')->where('processos', '1')->update(['processos' => 'Matrícula']);
        DB::table('programas')->where('processos', '0')->update(['processos' => 'Inscrição']);
    }

    public function down()
    {
        DB::table('programas')->where('processos', 'Matrícula')->update(['processos' => '1']);
        DB::table('programas')->where('processos', 'Inscrição')->update(['processos' => '0']);

        Schema::table('programas', function (Blueprint $table) {
            $table->boolean('processos')->change();
        });

        Schema::table('programas', function (Blueprint $table) {
            $table->renameColumn('processos', 'matricula');
        });
    }
}
