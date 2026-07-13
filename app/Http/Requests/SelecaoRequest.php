<?php

namespace App\Http\Requests;

use App\Models\Categoria;
use App\Models\Parametro;
use App\Models\Programa;
use Illuminate\Foundation\Http\FormRequest;

class SelecaoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        // o Laravel invoca prepareForValidation automaticamente neste ponto

        return [
            'categoria_id' => ['required', 'numeric'],
            'programa_id' => ['required_unless:categoria_id,' . Categoria::where('nome', 'Aluno Especial')->value('id')],
            'ingresso_semestre' => ['required', 'integer', 'between:0,2'],
            'ingresso_ano' => ['required', 'integer', 'digits:4'],
            'descricao' => ['max:255'],
            'fluxo_continuo' => [],
            'tem_taxa' => [],
            'solicitacoesisencaotaxa_data_inicio' => ['required_if:_solicitacaoisencaotaxa_datas_required_marker,1'],
            'solicitacoesisencaotaxa_hora_inicio' => ['required_if:_solicitacaoisencaotaxa_datas_required_marker,1'],
            'solicitacoesisencaotaxa_data_fim' => ['required_if:_solicitacaoisencaotaxa_datas_required_marker,1'],
            'solicitacoesisencaotaxa_hora_fim' => ['required_if:_solicitacaoisencaotaxa_datas_required_marker,1'],
            'inscricoesmatriculas_data_inicio' => ['required'],
            'inscricoesmatriculas_hora_inicio' => ['required'],
            'inscricoesmatriculas_data_fim' => ['required'],
            'inscricoesmatriculas_hora_fim' => ['required'],
            'boleto_data_vencimento' => ['required_if:_boleto_data_vencimento_required_marker,1'],
            'boleto_offset_vencimento' => ['required_if:_boleto_offset_vencimento_required_marker,1'],
            'boleto_valor' => ['required_if:tem_taxa,on', 'numeric'],
            'boleto_texto' => ['max:255'],
            'email_inscricaomatriculaaprovacao_texto' => ['max:255'],
            'email_inscricaomatricularejeicao_texto' => ['max:255'],
        ];
    }

    public function messages() {
        $fazInscricoes = false;
        $fazMatriculas = false;
        $classe_nome_singular = 'inscrição/matrícula';
        $classe_nome_plural = 'inscrições/matrículas';
        if ($this->filled('categoria_id') && (Categoria::find($this->categoria_id)?->nome != 'Aluno Especial')) {
            if ($this->filled('programa_id') && Programa::find($this->programa_id)?->fazInscricoes())
                $fazInscricoes = true;
            elseif ($this->filled('programa_id') && Programa::find($this->programa_id)?->fazMatriculas())
                $fazMatriculas = true;
        } else {
            if (Parametro::first()->especiaisFazInscricoes())
                $fazInscricoes = true;
            else
                $fazMatriculas = true;
        }
        if ($fazInscricoes) {
            $classe_nome_singular = 'inscrição';
            $classe_nome_plural = 'inscrições';
        }
        if ($fazMatriculas) {
            $classe_nome_singular = 'matrícula';
            $classe_nome_plural = 'matrículas';
        }

        return [
            'categoria_id.required' => 'A categoria é obrigatória!',
            'categoria_id.numeric' => 'A categoria é inválida!',
            'programa_id.required_unless' => 'O programa é obrigatório!',
            'descricao.max' => 'A descrição da seleção não pode exceder 255 caracteres!',
            'solicitacoesisencaotaxa_data_inicio.required_if' => 'A data de início das solicitações de isenção de taxa é obrigatória!',
            'solicitacoesisencaotaxa_hora_inicio.required_if' => 'A hora de início das solicitações de isenção de taxa é obrigatória!',
            'solicitacoesisencaotaxa_data_fim.required_if' => 'A data de fim das solicitações de isenção de taxa é obrigatória!',
            'solicitacoesisencaotaxa_hora_fim.required_if' => 'A hora de fim das solicitações de isenção de taxa é obrigatória!',
            'inscricoesmatriculas_data_inicio.required' => 'A data de início das ' . $classe_nome_plural . ' é obrigatória!',
            'inscricoesmatriculas_hora_inicio.required' => 'A hora de início das ' . $classe_nome_plural . ' é obrigatória!',
            'inscricoesmatriculas_data_fim.required' => 'A data de fim das ' . $classe_nome_plural . ' é obrigatória!',
            'inscricoesmatriculas_hora_fim.required' => 'A hora de fim das ' . $classe_nome_plural . ' é obrigatória!',
            'boleto_data_vencimento.required_if' => 'A data de vencimento do boleto é obrigatória!',
            'boleto_offset_vencimento.required_if' => 'A quantidade de dias úteis para pagamento do boleto é obrigatória!',
            'boleto_valor.required_if' => 'O valor do boleto é obrigatório!',
            'boleto_valor.numeric' => 'O valor do boleto é inválido!',
            'boleto_texto.max' => 'As eventuais informações adicionais no boleto não podem exceder 255 caracteres!',
            'email_inscricaomatriculaaprovacao_texto.max' => 'As eventuais informações adicionais no e-mail de aprovação da ' . $classe_nome_singular . ' não podem exceder 255 caracteres!',
            'email_inscricaomatricularejeicao_texto.max' => 'As eventuais informações adicionais no e-mail de rejeição da ' . $classe_nome_singular . ' não podem exceder 255 caracteres!',
        ];
    }

    protected function prepareForValidation() {
        $this->merge([
            '_solicitacaoisencaotaxa_datas_required_marker' => ($this->input('tem_taxa') === 'on' && $this->input('fluxo_continuo') !== 'on') ? 1 : 0,
            'boleto_valor' => str_replace(',', '.', (string) $this->boleto_valor),
            '_boleto_data_vencimento_required_marker' => ($this->input('tem_taxa') === 'on' && $this->input('fluxo_continuo') !== 'on') ? 1 : 0,
            '_boleto_offset_vencimento_required_marker' => ($this->input('tem_taxa') === 'on' && $this->input('fluxo_continuo') === 'on') ? 1 : 0,
        ]);
    }
}
