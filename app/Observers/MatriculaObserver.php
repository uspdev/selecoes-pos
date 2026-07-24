<?php

namespace App\Observers;

use App\Mail\MatriculaMail;
use App\Models\Disciplina;
use App\Models\Matricula;
use App\Models\Parametro;
use App\Models\Programa;
use App\Models\SolicitacaoIsencaoTaxa;
use App\Models\TipoArquivo;
use App\Models\User;
use App\Services\BoletoService;
use Illuminate\Support\Facades\DB;
use Uspdev\Replicado\Pessoa;

class MatriculaObserver
{
    protected $boletoService;

    public function __construct(BoletoService $boletoService)
    {
        $this->boletoService = $boletoService;
    }

    /**
     * Handle the Matricula "created" event.
     *
     * @param  \App\Models\Matricula  $matricula
     * @return void
     */
    public function created(Matricula $matricula)
    {
        // envia e-mail avisando o candidato da necessidade de enviar os arquivos e enviar a própria matrícula
        // envio do e-mail "8" do README.md
        $passo = 'início';
        $user = $matricula->pessoas('Autor');

        \Mail::to($user->email)
            ->queue(new MatriculaMail(compact('passo', 'matricula', 'user')));
    }

    /**
     * Listen to the Matricula updating event.
     *
     * @param  \App\Models\Matricula  $matricula
     * @return void
     */
    public function updating(Matricula $matricula)
    {
        //
    }

    /**
     * Handle the Matricula "updated" event.
     *
     * @param  \App\Models\Matricula  $matricula
     * @return void
     */
    public function updated(Matricula $matricula)
    {
        $user = $matricula->pessoas('Autor');
        $extras = json_decode($matricula->extras, true);
        $arquivos = [];
        $boleto_momento_envio = Parametro::first()->boleto_momento_envio;
        $email_secaoinformatica = Parametro::first()->email_secaoinformatica;

        if ($matricula->isDirty('estado')) {                                    // se a alteração na matrícula foi no estado
            if (($matricula->getOriginal('estado') == 'Aguardando Envio') &&    // se o estado anterior era Aguardando Envio
                ($matricula->estado == 'Enviada')) {                            // se o novo estado é Enviada
                // trata-se do envio da matrícula

                // envia e-mail para o candidato reconhecendo que ele enviou a matrícula
                // envio do e-mail "9" do README.md
                $passo = 'envio - para candidato';
                if ($matricula->selecao->tem_taxa &&
                    ($boleto_momento_envio == 'Envio da Inscrição/Matrícula') &&
                    !SolicitacaoIsencaoTaxa::where('extras->cpf', $extras['cpf'] ?? null)->where('selecao_id', $matricula->selecao->id)->where('estado', 'LIKE', 'Isenção de Taxa Aprovada%')->exists() &&
                    !$matricula->selecao->fazInscricoes())    // se houve inscrição, o(s) boleto(s) já foi(ram) gerado(s), então não o(s) gero(amos) aqui
                    if (!$matricula->selecao->exigeDisciplinas())
                        $arquivos = [$this->boletoService->gerarBoleto($matricula, 'Matricula')];    // gera boleto para a matrícula
                    else {
                        $resultado = $this->processa_disciplinas_alteradas($matricula, $user, $email_secaoinformatica);    // gera boleto(s) para a(s) disciplina(s)
                        $arquivos = $resultado['arquivos'];
                        if (!empty($arquivos) && !$resultado['is_primeiro_envio'])
                            // envia e-mail para o candidato com o(s) boleto(s)
                            // envio do e-mail "14" do README.md
                            $passo = 'envio disciplinas alteradas - para candidato';
                    }
                \Mail::to($user->email)
                    ->queue(new MatriculaMail(compact('passo', 'matricula', 'user', 'arquivos', 'email_secaoinformatica')));

                // envia e-mails avisando o serviço de pós-graduação sobre a realização da matrícula
                // envio do e-mail "13" do README.md
                $passo = 'envio - para gestores';
                foreach (collect((new Programa)->obterResponsaveis())->firstWhere('funcao', 'Serviço de Pós-Graduação')['users'] as $servicoposgraduacao) {
                    $responsavel_nome = 'Prezado(a) Sr.(a) ' . Pessoa::obterNome($servicoposgraduacao->codpes);
                    \Mail::to($servicoposgraduacao->email)
                        ->queue(new MatriculaMail(compact('passo', 'matricula', 'user', 'responsavel_nome')));
                }
            } elseif (($matricula->getOriginal('estado') == 'Em Pré-Avaliação') &&    // se o estado anterior era Em Pré-Avaliação
                      ($matricula->estado == 'Pré-Aprovada')) {                       // se o novo estado é Pré-Aprovada
                // trata-se da pré-aprovação da matrícula

                // envia e-mail avisando o candidato da pré-aprovação da matrícula
                // envio do e-mail "16" do README.md
                $passo = 'pré-aprovação';
                $link_acompanhamento = (($matricula->selecao->categoria?->nome == 'Aluno Especial') ? Parametro::first()->link_acompanhamento_especiais : $matricula->selecao->programa?->link_acompanhamento);
                \Mail::to($user->email)
                    ->queue(new MatriculaMail(compact('passo', 'matricula', 'user', 'link_acompanhamento')));

            } elseif (($matricula->getOriginal('estado') == 'Em Pré-Avaliação') &&    // se o estado anterior era Em Pré-Avaliação
                      ($matricula->estado == 'Pré-Rejeitada')) {                      // se o novo estado é Pré-Rejeitada
                // trata-se da pré-rejeição da matrícula

                // envia e-mail avisando o candidato da pré-rejeição da matrícula
                // envio do e-mail "17" do README.md
                $passo = 'pré-rejeição';
                \Mail::to($user->email)
                    ->queue(new MatriculaMail(compact('passo', 'matricula', 'user')));

            } elseif (($matricula->getOriginal('estado') == 'Em Avaliação') &&        // se o estado anterior era Em Avaliação
                      ($matricula->estado == 'Aprovada')) {                           // se o novo estado é Aprovada
                // trata-se da aprovação da matrícula

                // verifica se a seleção tem taxa e se o candidato não tem isenção de taxa aprovada
                if ($matricula->selecao->tem_taxa &&
                    ($boleto_momento_envio == 'Aprovação da Inscrição/Matrícula') &&
                    !SolicitacaoIsencaoTaxa::where('extras->cpf', $extras['cpf'] ?? null)->where('selecao_id', $matricula->selecao->id)->where('estado', 'LIKE', 'Isenção de Taxa Aprovada%')->exists() &&
                    !$matricula->selecao->fazInscricoes())    // se houve inscrição, o(s) boleto(s) já foi(ram) gerado(s), então não o(s) gero(amos) aqui
                    if (!$matricula->selecao->exigeDisciplinas())
                        $arquivos = [$this->boletoService->gerarBoleto($matricula, 'Matricula')];    // gera boleto para a matrícula
                    else {
                        $disciplinas_id = (isset($extras['disciplinas']) ? $extras['disciplinas'] : []);
                        foreach ($disciplinas_id as $disciplina_id) {
                            $disciplina = Disciplina::find($disciplina_id);
                            $arquivos[] = $this->boletoService->gerarBoleto($matricula, 'Matricula', $disciplina->sigla);    // gera boleto(s) para a(s) disciplina(s)
                        }
                    }

                // envia e-mail avisando o candidato da aprovação da matrícula
                // envio do e-mail "18" do README.md
                $passo = 'aprovação';
                \Mail::to($user->email)
                    ->queue(new MatriculaMail(compact('passo', 'matricula', 'user', 'arquivos', 'email_secaoinformatica')));

            } elseif (($matricula->getOriginal('estado') == 'Em Avaliação') &&        // se o estado anterior era Em Avaliação
                      ($matricula->estado == 'Rejeitada')) {                          // se o novo estado é Rejeitada
                // trata-se da rejeição da matrícula

                // envia e-mail avisando o candidato da rejeição da matrícula
                // envio do e-mail "19" do README.md
                $passo = 'rejeição';
                \Mail::to($user->email)
                    ->queue(new MatriculaMail(compact('passo', 'matricula', 'user')));
            }
        }
    }

    private function processa_disciplinas_alteradas(Matricula $matricula, User $user, ?string $email_secaoinformatica) : array
    {
        $extras = json_decode($matricula->extras, true);
        $disciplinas_id = (isset($extras['disciplinas']) ? $extras['disciplinas'] : []);

        // obtém o conjunto de disciplinas do envio anterior
        $disciplinas_sigla_anterior = $matricula->arquivos()->whereHas('tipoarquivo', function ($query) { $query->where('nome', 'Boleto(s) de Pagamento'); })->pluck('disciplina')->toArray();
        $disciplinas_id_anterior = Disciplina::whereIn('sigla', $disciplinas_sigla_anterior)->pluck('id')->toArray();

        // transaction para não ter problema de inconsistência do DB
        $arquivos = DB::transaction(function () use ($matricula, $disciplinas_id, $disciplinas_id_anterior) {

            // marca como removidas as disciplinas as quais o candidato removeu
            $tipoarquivo_boletodisciplinasremovidas = TipoArquivo::where('classe_nome', 'Matrículas')->where('nome', 'Boleto(s) de Pagamento - Disciplinas Removidas')->first();
            foreach (array_diff($disciplinas_id_anterior, $disciplinas_id) as $disciplina_id_removida) {
                $disciplina = Disciplina::find($disciplina_id_removida);
                foreach ($matricula->arquivos()->whereHas('tipoarquivo', function ($query) { $query->where('nome', 'Boleto(s) de Pagamento'); })->where('disciplina', $disciplina->sigla)->get() as $arquivo) {
                    $matricula->arquivos()->updateExistingPivot(
                        $arquivo->id,                                                                               // estranhamente, o Laravel precisa que eu passe o arquivo_id aqui, mesmo que eu tenha começado este comando com $matricula (ou seja, ele deveria saber qual é a matrícula)
                        ['tipo' => 'Boleto(s) de Pagamento - Disciplinas Removidas']                                // atualiza o tipo do arquivo para "Boleto(s) de Pagamento - Disciplinas Removidas"
                    );
                    $arquivo->tipoarquivo_id = $tipoarquivo_boletodisciplinasremovidas->id;             // atualiza o tipo do arquivo para "Boleto(s) de Pagamento - Disciplinas Removidas"
                    $arquivo->nome_original = str_replace('_Boleto_', '_BoletoDiscRemov_', $arquivo->nome_original);    // atualiza o nome do arquivo para refletir o novo tipo
                    $arquivo->save();
                }
            }

            // gera boletos para as novas disciplinas deste reenvio
            $arquivos = [];
            foreach (array_diff($disciplinas_id, $disciplinas_id_anterior) as $disciplina_id_nova) {
                $disciplina = Disciplina::find($disciplina_id_nova);
                $arquivos[] = $this->boletoService->gerarBoleto($matricula, 'Matricula', $disciplina->sigla);
            }

            return $arquivos;
        });

        return [
            'arquivos' => $arquivos,
            'is_primeiro_envio' => empty($disciplinas_id_anterior)
        ];
    }

    /**
     * Handle the Matricula "deleted" event.
     *
     * @param  \App\Models\Matricula  $matricula
     * @return void
     */
    public function deleted(Matricula $matricula)
    {
        //
    }

    /**
     * Handle the Matricula "restored" event.
     *
     * @param  \App\Models\Matricula  $matricula
     * @return void
     */
    public function restored(Matricula $matricula)
    {
        //
    }

    /**
     * Handle the Matricula "force deleted" event.
     *
     * @param  \App\Models\Matricula  $matricula
     * @return void
     */
    public function forceDeleted(Matricula $matricula)
    {
        //
    }
}
