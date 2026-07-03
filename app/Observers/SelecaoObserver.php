<?php

namespace App\Observers;

use App\Mail\SelecaoMail;
use App\Models\Parametro;
use App\Models\Selecao;

class SelecaoObserver
{
    /**
     * Handle the Selecao "created" event.
     *
     * @param  \App\Models\Selecao  $selecao
     * @return void
     */
    public function created(Selecao $selecao)
    {
        $this->atualizaNome($selecao);
    }

    /**
     * Listen to the Selecao updating event.
     *
     * @param  \App\Models\Selecao  $selecao
     * @return void
     */
    public function updating(Selecao $selecao)
    {
        //
    }

    /**
     * Handle the Selecao "updated" event.
     *
     * @param  \App\Models\Selecao  $selecao
     * @return void
     */
    public function updated(Selecao $selecao)
    {
        $this->atualizaNome($selecao);

        if ($selecao->isDirty('estado'))                                           // se a alteração na seleção foi no estado
            if (($selecao->getOriginal('estado') == 'Em Elaboração') &&            // se o estado anterior era Em Elaboração
                (str_starts_with($selecao->estado, 'Aguardando Início das ') ||    // se o novo estado é algum desses
                 str_starts_with($selecao->estado, 'Período de '))) {

                // envia e-mail avisando o gerenciamento do site da unidade sobre a seleção
                // envio do e-mail "1" do README.md
                $passo = 'seleção elaborada';
                $email_gerenciamentosite = Parametro::first()->email_gerenciamentosite;
                if ($email_gerenciamentosite)
                    \Mail::to($email_gerenciamentosite)
                        ->queue(new SelecaoMail(compact('passo', 'selecao')));
            }
    }

    /**
     * Handle the Selecao "deleted" event.
     *
     * @param  \App\Models\Selecao  $selecao
     * @return void
     */
    public function deleted(Selecao $selecao)
    {
        //
    }

    /**
     * Handle the Selecao "restored" event.
     *
     * @param  \App\Models\Selecao  $selecao
     * @return void
     */
    public function restored(Selecao $selecao)
    {
        //
    }

    /**
     * Handle the Selecao "force deleted" event.
     *
     * @param  \App\Models\Selecao  $selecao
     * @return void
     */
    public function forceDeleted(Selecao $selecao)
    {
        //
    }

    /**
     * Atualiza o campo nome da seleção com base na categoria, programa e ingresso
     *
     * @param  \App\Models\Selecao  $selecao
     * @return void
     */
    private function atualizaNome(Selecao $selecao)
    {
        $selecao_nome_novo = (($selecao->categoria->nome === 'Aluno Especial') ? 'Aluno Especial' : $selecao->programa->sigla . ' para ingresso');

        if ($selecao->categoria->nome === 'Aluno Especial')
            $selecao_nome_novo .= ' ';
        else
            $selecao_nome_novo .= (($selecao->ingresso_semestre == 0) ? ' em ' : ' no ');

        $selecao_nome_novo .= (($selecao->ingresso_semestre == 0) ? $selecao->ingresso_ano : $selecao->ingresso_semestre . 'º semestre de ' . $selecao->ingresso_ano);

        // verifica se o nome realmente precisa ser alterado para evitar operações desnecessárias
        if ($selecao->nome !== $selecao_nome_novo)
            $selecao->updateQuietly(['nome' => $selecao_nome_novo]);
    }
}
