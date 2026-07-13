<?php

namespace App\Jobs;

use App\Mail\SelecaoMail;
use App\Utils\ClasseUtils;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AlertaCandidatoIncompletude implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $objeto_id;
    private $classe_nome;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($objeto_id, $classe_nome)
    {
        $this->objeto_id = $objeto_id;
        $this->classe_nome = $classe_nome;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $objeto = ClasseUtils::obterClasse($this->classe_nome)::findOrFail($this->objeto_id);
        if ($objeto->estado === 'Aguardando Envio')
            switch ($this->classe_nome) {
                case 'SolicitacaoIsencaoTaxa':
                    // envia e-mail para o candidato que não enviou sua solicitação de isenção de taxa a respeito da proximidade do término do período de solicitações de isenção de taxa
                    // envio do e-mail "21" do README.md
                    $passo = 'alerta de proximidade do fim das solicitações de isenção de taxa';
                    $selecao = $objeto->selecao;
                    $extras = json_decode($objeto->extras, true);
                    $candidatonome = $extras['nome'];
                    \Mail::to($extras['e_mail'])
                        ->queue(new SelecaoMail(compact('passo', 'selecao', 'candidatonome')));
                    break;

                case 'Inscricao':
                    // envia e-mail para o candidato que não enviou sua inscrição a respeito da proximidade do término do período de inscrições
                    // envio do e-mail "22" do README.md
                    $passo = 'alerta de proximidade do fim das inscrições';
                    $selecao = $objeto->selecao;
                    $extras = json_decode($objeto->extras, true);
                    $candidatonome = $extras['nome'];
                    \Mail::to($extras['e_mail'])
                        ->queue(new SelecaoMail(compact('passo', 'selecao', 'candidatonome')));
                    break;

                case 'Matricula':
                    // envia e-mail para o candidato que não enviou sua matrícula a respeito da proximidade do término do período de matrículas
                    // envio do e-mail "23" do README.md
                    $passo = 'alerta de proximidade do fim das matrículas';
                    $selecao = $objeto->selecao;
                    $extras = json_decode($objeto->extras, true);
                    $candidatonome = $extras['nome'];
                    \Mail::to($extras['e_mail'])
                        ->queue(new SelecaoMail(compact('passo', 'selecao', 'candidatonome')));
            }
    }
}
