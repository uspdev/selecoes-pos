<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateSelecoesTableSplitDates extends Migration
{
    public function up()
    {
        Schema::table('selecoes', function (Blueprint $table) {
            $table->renameColumn('inscricoesmatriculas_datahora_inicio', 'inscricoes_datahora_inicio');
            $table->renameColumn('inscricoesmatriculas_datahora_fim', 'inscricoes_datahora_fim');
            $table->renameColumn('email_inscricaomatriculaaprovacao_texto', 'email_inscricaoaprovacao_texto');
            $table->renameColumn('email_inscricaomatricularejeicao_texto', 'email_inscricaorejeicao_texto');
        });
        Schema::table('selecoes', function (Blueprint $table) {
            $table->dateTime('inscricoes_datahora_inicio')->nullable()->change();
            $table->dateTime('inscricoes_datahora_fim')->nullable()->change();
        });
        Schema::table('selecoes', function (Blueprint $table) {
            $table->dateTime('matriculas_datahora_inicio')->nullable()->after('inscricoes_datahora_fim');
            $table->dateTime('matriculas_datahora_fim')->nullable()->after('matriculas_datahora_inicio');
            $table->text('email_matriculaaprovacao_texto')->nullable()->after('email_inscricaorejeicao_texto');
            $table->text('email_matricularejeicao_texto')->nullable()->after('email_matriculaaprovacao_texto');
        });

        $parametros = DB::table('parametros')->first();
        $alunos_especiais_faz_inscricoes = $parametros && (strpos($parametros->processos_especiais, 'Inscrição') !== false);
        $alunos_especiais_faz_matriculas = $parametros && (strpos($parametros->processos_especiais, 'Matrícula') !== false);
        $selecoes = DB::table('selecoes')
            ->leftJoin('categorias', 'selecoes.categoria_id', '=', 'categorias.id')
            ->leftJoin('programas', 'selecoes.programa_id', '=', 'programas.id')
            ->select('selecoes.*', 'categorias.nome as categoria_nome', 'programas.processos as programa_processos')
            ->get();
        foreach ($selecoes as $selecao) {
            $fazInscricoes = false;
            $fazMatriculas = false;
            if ($selecao->categoria_nome === 'Aluno Especial') {
                $fazInscricoes = $alunos_especiais_faz_inscricoes;
                $fazMatriculas = $alunos_especiais_faz_matriculas;
            } else {
                $fazInscricoes = $selecao->programa_processos && (strpos($selecao->programa_processos, 'Inscrição') !== false);
                $fazMatriculas = $selecao->programa_processos && (strpos($selecao->programa_processos, 'Matrícula') !== false);
            }
            $dados_a_atualizar = [];
            if ($selecao->fluxo_continuo) {
                if ($fazInscricoes && $fazMatriculas) {
                    $dados_a_atualizar['solicitacoesisencaotaxa_datahora_inicio'] = $selecao->inscricoes_datahora_inicio;
                    $dados_a_atualizar['solicitacoesisencaotaxa_datahora_fim'] = $selecao->inscricoes_datahora_fim;
                    $dados_a_atualizar['matriculas_datahora_inicio'] = $selecao->inscricoes_datahora_inicio;
                    $dados_a_atualizar['matriculas_datahora_fim'] = $selecao->inscricoes_datahora_fim;
                } elseif ($fazInscricoes) {
                    $dados_a_atualizar['solicitacoesisencaotaxa_datahora_inicio'] = $selecao->inscricoes_datahora_inicio;
                    $dados_a_atualizar['solicitacoesisencaotaxa_datahora_fim'] = $selecao->inscricoes_datahora_fim;
                } elseif ($fazMatriculas) {
                    $dados_a_atualizar['solicitacoesisencaotaxa_datahora_inicio'] = $selecao->inscricoes_datahora_inicio;
                    $dados_a_atualizar['solicitacoesisencaotaxa_datahora_fim'] = $selecao->inscricoes_datahora_fim;
                    $dados_a_atualizar['matriculas_datahora_inicio'] = $selecao->inscricoes_datahora_inicio;
                    $dados_a_atualizar['matriculas_datahora_fim'] = $selecao->inscricoes_datahora_fim;
                    $dados_a_atualizar['inscricoes_datahora_inicio'] = null;
                    $dados_a_atualizar['inscricoes_datahora_fim'] = null;
                    $dados_a_atualizar['email_matriculaaprovacao_texto'] = $selecao->email_inscricaoaprovacao_texto;
                    $dados_a_atualizar['email_matricularejeicao_texto'] = $selecao->email_inscricaorejeicao_texto;
                    $dados_a_atualizar['email_inscricaoaprovacao_texto'] = null;
                    $dados_a_atualizar['email_inscricaorejeicao_texto'] = null;
                }
            } elseif ($fazMatriculas) {
                $dados_a_atualizar = [
                    'matriculas_datahora_inicio' => $selecao->inscricoes_datahora_inicio,
                    'matriculas_datahora_fim' => $selecao->inscricoes_datahora_fim,
                    'email_matriculaaprovacao_texto' => $selecao->email_inscricaoaprovacao_texto,
                    'email_matricularejeicao_texto' => $selecao->email_inscricaorejeicao_texto,
                    'inscricoes_datahora_inicio' => null,
                    'inscricoes_datahora_fim' => null,
                    'email_inscricaoaprovacao_texto' => null,
                    'email_inscricaorejeicao_texto' => null,
                ];
            }
            if (!empty($dados_a_atualizar))
                DB::table('selecoes')->where('id', $selecao->id)->update($dados_a_atualizar);
        }
    }

    public function down()
    {
        $selecoes = DB::table('selecoes')->get();
        foreach ($selecoes as $selecao)
            if (!is_null($selecao->matriculas_datahora_inicio) || !is_null($selecao->email_matriculaaprovacao_texto))
                DB::table('selecoes')->where('id', $selecao->id)->update([
                    'inscricoes_datahora_inicio' => $selecao->inscricoes_datahora_inicio ?? $selecao->matriculas_datahora_inicio,
                    'inscricoes_datahora_fim' => $selecao->inscricoes_datahora_fim ?? $selecao->matriculas_datahora_fim,
                    'email_inscricaoaprovacao_texto' => $selecao->email_inscricaoaprovacao_texto ?? $selecao->email_matriculaaprovacao_texto,
                    'email_inscricaorejeicao_texto' => $selecao->email_inscricaorejeicao_texto ?? $selecao->email_matricularejeicao_texto,
                ]);

        Schema::table('selecoes', function (Blueprint $table) {
            $table->dropColumn([
                'matriculas_datahora_inicio',
                'matriculas_datahora_fim',
                'email_matriculaaprovacao_texto',
                'email_matricularejeicao_texto',
            ]);
        });
        Schema::table('selecoes', function (Blueprint $table) {
            $table->renameColumn('inscricoes_datahora_inicio', 'inscricoesmatriculas_datahora_inicio');
            $table->renameColumn('inscricoes_datahora_fim', 'inscricoesmatriculas_datahora_fim');
            $table->renameColumn('email_inscricaoaprovacao_texto', 'email_inscricaomatriculaaprovacao_texto');
            $table->renameColumn('email_inscricaorejeicao_texto', 'email_inscricaomatricularejeicao_texto');
        });
        Schema::table('selecoes', function (Blueprint $table) {
            $table->dateTime('inscricoesmatriculas_datahora_inicio')->nullable(false)->change();
            $table->dateTime('inscricoesmatriculas_datahora_fim')->nullable(false)->change();
        });
    }
}
