<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateParametrosTableAddProcessosEspeciais extends Migration
{
    public function up()
    {
        Schema::table('parametros', function (Blueprint $table) {
            $table->string('processos_especiais')->nullable()->after('link_acompanhamento_especiais');
        });
    }

    public function down()
    {
        Schema::table('parametros', function (Blueprint $table) {
            $table->dropColumn('processos_especiais');
        });
    }
}
