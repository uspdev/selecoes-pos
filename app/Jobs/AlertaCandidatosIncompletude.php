<?php

namespace App\Jobs;

use App\Mail\SelecaoMail;
use App\Models\Selecao;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AlertaCandidatosIncompletude implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $selecao_id;
    private $classe_nome;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($selecao_id, $classe_nome)
    {
        $this->selecao_id = $selecao_id;
        $this->classe_nome = $classe_nome;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $selecao = Selecao::where('id', $this->selecao_id)->first();
        switch ($this->classe_nome) {
            case 'SolicitacaoIsencaoTaxa':
                // envia e-mail para os candidatos que não enviaram suas solicitações de isenção de taxa a respeito da proximidade do término do período de solicitações de isenção de taxa
                // envio do e-mail "24" do README.md
                $passo = 'alerta de proximidade do fim das solicitações de isenção de taxa';
                foreach ($selecao->solicitacoesisencaotaxa as $solicitacaoisencaotaxa)
                    if ($solicitacaoisencaotaxa->estado === 'Aguardando Envio') {
                        $extras = json_decode($solicitacaoisencaotaxa->extras, true);
                        $candidatonome = $extras['nome'];
                        \Mail::to($extras['e_mail'])
                            ->queue(new SelecaoMail(compact('passo', 'selecao', 'candidatonome')));
                    }
                break;

            case 'Inscricao':
                // envia e-mail para os candidatos que não enviaram suas inscrições a respeito da proximidade do término do período de inscrições
                // envio do e-mail "25" do README.md
                $passo = 'alerta de proximidade do fim das inscrições';
                foreach ($selecao->inscricoes as $inscricao)
                    if ($inscricao->estado === 'Aguardando Envio') {
                        $extras = json_decode($inscricao->extras, true);
                        $candidatonome = $extras['nome'];
                        \Mail::to($extras['e_mail'])
                            ->queue(new SelecaoMail(compact('passo', 'selecao', 'candidatonome')));
                    }
                break;

            case 'Matricula':
                // envia e-mail para os candidatos que não enviaram suas matrículas a respeito da proximidade do término do período de matrículas
                // envio do e-mail "26" do README.md
                $passo = 'alerta de proximidade do fim das matrículas';
                foreach ($selecao->matriculas as $matricula)
                    if ($matricula->estado === 'Aguardando Envio') {
                        $extras = json_decode($matricula->extras, true);
                        $candidatonome = $extras['nome'];
                        \Mail::to($extras['e_mail'])
                            ->queue(new SelecaoMail(compact('passo', 'selecao', 'candidatonome')));
                    }
        }
    }
}
