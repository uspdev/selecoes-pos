<?php

namespace App\Observers;

use App\Mail\InscricaoMail;
use App\Models\Disciplina;
use App\Models\Inscricao;
use App\Models\Parametro;
use App\Models\Programa;
use App\Models\SolicitacaoIsencaoTaxa;
use App\Models\TipoArquivo;
use App\Models\User;
use App\Services\BoletoService;
use Illuminate\Support\Facades\DB;
use Uspdev\Replicado\Pessoa;

class InscricaoObserver
{
    protected $boletoService;

    public function __construct(BoletoService $boletoService)
    {
        $this->boletoService = $boletoService;
    }

    /**
     * Handle the Inscricao "created" event.
     *
     * @param  \App\Models\Inscricao  $inscricao
     * @return void
     */
    public function created(Inscricao $inscricao)
    {
        // envia e-mail avisando o candidato da necessidade de enviar os arquivos e enviar a própria inscrição
        // envio do e-mail "8" do README.md
        $passo = 'início';
        $user = $inscricao->pessoas('Autor');

        \Mail::to($user->email)
            ->queue(new InscricaoMail(compact('passo', 'inscricao', 'user')));
    }

    /**
     * Listen to the Inscricao updating event.
     *
     * @param  \App\Models\Inscricao  $inscricao
     * @return void
     */
    public function updating(Inscricao $inscricao)
    {
        //
    }

    /**
     * Handle the Inscricao "updated" event.
     *
     * @param  \App\Models\Inscricao  $inscricao
     * @return void
     */
    public function updated(Inscricao $inscricao)
    {
        $user = $inscricao->pessoas('Autor');
        $extras = json_decode($inscricao->extras, true);
        $arquivos = [];
        $boleto_momento_envio = Parametro::first()->boleto_momento_envio;
        $email_secaoinformatica = Parametro::first()->email_secaoinformatica;

        if ($inscricao->isDirty('estado')) {                                    // se a alteração na inscrição foi no estado
            if (($inscricao->getOriginal('estado') == 'Aguardando Envio') &&    // se o estado anterior era Aguardando Envio
                ($inscricao->estado == 'Enviada')) {                            // se o novo estado é Enviada
                // trata-se do envio da inscrição

                // envia e-mail para o candidato reconhecendo que ele enviou a inscrição
                // envio do e-mail "9" do README.md
                $passo = 'envio - para candidato';
                if ($inscricao->selecao->tem_taxa &&
                    ($boleto_momento_envio == 'Envio da Inscrição/Matrícula') &&
                    !SolicitacaoIsencaoTaxa::where('extras->cpf', $extras['cpf'] ?? null)->where('selecao_id', $inscricao->selecao->id)->where('estado', 'LIKE', 'Isenção de Taxa Aprovada%')->exists())
                    if ($inscricao->selecao->categoria->nome !== 'Aluno Especial')
                        $arquivos = [$this->boletoService->gerarBoleto($inscricao, 'Inscricao')];    // gera boleto para a inscrição
                    else {
                        $resultado = $this->processa_disciplinas_alteradas($inscricao, $user, $email_secaoinformatica);    // gera boleto(s) para a(s) disciplina(s)
                        $arquivos = $resultado['arquivos'];
                        if (!empty($arquivos) && !$resultado['is_primeiro_envio'])
                            // envia e-mail para o candidato com o(s) boleto(s)
                            // envio do e-mail "14" do README.md
                            $passo = 'envio disciplinas alteradas - para candidato';
                    }
                \Mail::to($user->email)
                    ->queue(new InscricaoMail(compact('passo', 'inscricao', 'user', 'arquivos', 'email_secaoinformatica')));

                if ($inscricao->selecao->categoria->nome != 'Aluno Especial') {
                    // envia e-mail avisando a secretaria do programa da seleção da inscrição sobre a realização da inscrição
                    // envio do e-mail "10" do README.md
                    $passo = 'envio - para gestores';
                    $responsavel_nome = 'Prezados(as) Srs(as). da Secretaria do Programa ' . $inscricao->selecao->programa->nomeCompleto();
                    \Mail::to($inscricao->selecao->programa->email_secretaria)
                        ->queue(new InscricaoMail(compact('passo', 'inscricao', 'user', 'responsavel_nome')));

                    // envia e-mails avisando os coordenadores do programa da seleção da inscrição sobre a realização da inscrição
                    // envio do e-mail "11" do README.md
                    foreach (collect($inscricao->selecao->programa->obterResponsaveis())->firstWhere('funcao', 'Coordenadores do Programa')['users'] as $coordenador) {
                        $responsavel_nome = 'Prezado(a) Sr(a). ' . Pessoa::obterNome($coordenador->codpes);
                        \Mail::to($coordenador->email)
                            ->queue(new InscricaoMail(compact('passo', 'inscricao', 'user', 'responsavel_nome')));
                    }
                } else {
                    // envia e-mails avisando o serviço de pós-graduação sobre a realização da inscrição
                    // envio do e-mail "12" do README.md
                    $passo = 'envio - para gestores';
                    foreach (collect((new Programa)->obterResponsaveis())->firstWhere('funcao', 'Serviço de Pós-Graduação')['users'] as $servicoposgraduacao) {
                        $responsavel_nome = 'Prezado(a) Sr.(a) ' . Pessoa::obterNome($servicoposgraduacao->codpes);
                        \Mail::to($servicoposgraduacao->email)
                            ->queue(new InscricaoMail(compact('passo', 'inscricao', 'user', 'responsavel_nome')));
                    }
                }
            } elseif (($inscricao->getOriginal('estado') == 'Em Pré-Avaliação') &&    // se o estado anterior era Em Pré-Avaliação
                      ($inscricao->estado == 'Pré-Aprovada')) {                       // se o novo estado é Pré-Aprovada
                // trata-se da pré-aprovação da inscrição

                // envia e-mail avisando o candidato da pré-aprovação da inscrição
                // envio do e-mail "16" do README.md
                $passo = 'pré-aprovação';
                $link_acompanhamento = (($inscricao->selecao->categoria->nome == 'Aluno Especial') ? Parametro::first()->link_acompanhamento_especiais : $inscricao->selecao->programa->link_acompanhamento);
                \Mail::to($user->email)
                    ->queue(new InscricaoMail(compact('passo', 'inscricao', 'user', 'link_acompanhamento')));

            } elseif (($inscricao->getOriginal('estado') == 'Em Pré-Avaliação') &&    // se o estado anterior era Em Pré-Avaliação
                      ($inscricao->estado == 'Pré-Rejeitada')) {                      // se o novo estado é Pré-Rejeitada
                // trata-se da pré-rejeição da inscrição

                // envia e-mail avisando o candidato da pré-rejeição da inscrição
                // envio do e-mail "17" do README.md
                $passo = 'pré-rejeição';
                \Mail::to($user->email)
                    ->queue(new InscricaoMail(compact('passo', 'inscricao', 'user')));

            } elseif (($inscricao->getOriginal('estado') == 'Em Avaliação') &&        // se o estado anterior era Em Avaliação
                      ($inscricao->estado == 'Aprovada')) {                           // se o novo estado é Aprovada
                // trata-se da aprovação da inscrição

                // verifica se a seleção tem taxa e se o candidato não tem isenção de taxa aprovada
                if ($inscricao->selecao->tem_taxa &&
                    ($boleto_momento_envio == 'Aprovação da Inscrição/Matrícula') &&
                    !SolicitacaoIsencaoTaxa::where('extras->cpf', $extras['cpf'] ?? null)->where('selecao_id', $inscricao->selecao->id)->where('estado', 'LIKE', 'Isenção de Taxa Aprovada%')->exists())
                    if ($inscricao->selecao->categoria->nome !== 'Aluno Especial')
                        $arquivos = [$this->boletoService->gerarBoleto($inscricao, 'Inscricao')];    // gera boleto para a inscrição
                    else {
                        $disciplinas_id = (isset($extras['disciplinas']) ? $extras['disciplinas'] : []);
                        foreach ($disciplinas_id as $disciplina_id) {
                            $disciplina = Disciplina::find($disciplina_id);
                            $arquivos[] = $this->boletoService->gerarBoleto($inscricao, 'Inscricao', $disciplina->sigla);    // gera boleto(s) para a(s) disciplina(s)
                        }
                    }

                // envia e-mail avisando o candidato da aprovação da inscrição
                // envio do e-mail "18" do README.md
                $passo = 'aprovação';
                \Mail::to($user->email)
                    ->queue(new InscricaoMail(compact('passo', 'inscricao', 'user', 'arquivos', 'email_secaoinformatica')));

            } elseif (($inscricao->getOriginal('estado') == 'Em Avaliação') &&        // se o estado anterior era Em Avaliação
                      ($inscricao->estado == 'Rejeitada')) {                          // se o novo estado é Rejeitada
                // trata-se da rejeição da inscrição

                // envia e-mail avisando o candidato da rejeição da inscrição
                // envio do e-mail "19" do README.md
                $passo = 'rejeição';
                \Mail::to($user->email)
                    ->queue(new InscricaoMail(compact('passo', 'inscricao', 'user')));
            }
        }
    }

    private function processa_disciplinas_alteradas(Inscricao $inscricao, User $user, ?string $email_secaoinformatica) : array
    {
        $extras = json_decode($inscricao->extras, true);
        $disciplinas_id = (isset($extras['disciplinas']) ? $extras['disciplinas'] : []);

        // obtém o conjunto de disciplinas do envio anterior
        $disciplinas_sigla_anterior = $inscricao->arquivos()->whereHas('tipoarquivo', function ($query) { $query->where('nome', 'Boleto(s) de Pagamento'); })->pluck('disciplina')->toArray();
        $disciplinas_id_anterior = Disciplina::whereIn('sigla', $disciplinas_sigla_anterior)->pluck('id')->toArray();

        // transaction para não ter problema de inconsistência do DB
        $arquivos = DB::transaction(function () use ($inscricao, $disciplinas_id, $disciplinas_id_anterior) {

            // marca como removidas as disciplinas as quais o candidato removeu
            $tipoarquivo_boletodisciplinasremovidas = TipoArquivo::where('classe_nome', 'Inscrições')->where('nome', 'Boleto(s) de Pagamento - Disciplinas Removidas')->first();
            foreach (array_diff($disciplinas_id_anterior, $disciplinas_id) as $disciplina_id_removida) {
                $disciplina = Disciplina::find($disciplina_id_removida);
                foreach ($inscricao->arquivos()->whereHas('tipoarquivo', function ($query) { $query->where('nome', 'Boleto(s) de Pagamento'); })->where('disciplina', $disciplina->sigla)->get() as $arquivo) {
                    $inscricao->arquivos()->updateExistingPivot(
                        $arquivo->id,                                                                               // estranhamente, o Laravel precisa que eu passe o arquivo_id aqui, mesmo que eu tenha começado este comando com $inscricao (ou seja, ele deveria saber qual é a inscrição)
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
                $arquivos[] = $this->boletoService->gerarBoleto($inscricao, 'Inscricao', $disciplina->sigla);
            }

            return $arquivos;
        });

        return [
            'arquivos' => $arquivos,
            'is_primeiro_envio' => empty($disciplinas_id_anterior)
        ];
    }

    /**
     * Handle the Inscricao "deleted" event.
     *
     * @param  \App\Models\Inscricao  $inscricao
     * @return void
     */
    public function deleted(Inscricao $inscricao)
    {
        //
    }

    /**
     * Handle the Inscricao "restored" event.
     *
     * @param  \App\Models\Inscricao  $inscricao
     * @return void
     */
    public function restored(Inscricao $inscricao)
    {
        //
    }

    /**
     * Handle the Inscricao "force deleted" event.
     *
     * @param  \App\Models\Inscricao  $inscricao
     * @return void
     */
    public function forceDeleted(Inscricao $inscricao)
    {
        //
    }
}
