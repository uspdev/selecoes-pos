<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSelecoesTableData extends Migration
{
    public function up()
    {
        DB::table('selecoes')->where('fluxo_continuo', 1)->update([
            'solicitacoesisencaotaxa_datahora_inicio' => DB::raw('inscricoesmatriculas_datahora_inicio'),
            'solicitacoesisencaotaxa_datahora_fim' => DB::raw('inscricoesmatriculas_datahora_fim'),
        ]);
    }

    public function down()
    {
        DB::table('selecoes')->where('fluxo_continuo', 1)->update([
            'solicitacoesisencaotaxa_datahora_inicio' => null,
            'solicitacoesisencaotaxa_datahora_fim' => null,
        ]);
    }
}
