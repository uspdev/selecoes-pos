<?php

namespace App\Http\Requests;

use App\Models\Categoria;
use App\Models\Parametro;
use App\Models\Programa;
use App\Models\Selecao;
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
            'inscricoes_data_inicio' => ['required_if:_inscricao_datas_required_marker,1'],
            'inscricoes_hora_inicio' => ['required_if:_inscricao_datas_required_marker,1'],
            'inscricoes_data_fim' => ['required_if:_inscricao_datas_required_marker,1'],
            'inscricoes_hora_fim' => ['required_if:_inscricao_datas_required_marker,1'],
            'matriculas_data_inicio' => ['required_if:_matricula_datas_required_marker,1'],
            'matriculas_hora_inicio' => ['required_if:_matricula_datas_required_marker,1'],
            'matriculas_data_fim' => ['required_if:_matricula_datas_required_marker,1'],
            'matriculas_hora_fim' => ['required_if:_matricula_datas_required_marker,1'],
            'boleto_data_vencimento' => ['required_if:_boleto_data_vencimento_required_marker,1'],
            'boleto_offset_vencimento' => ['required_if:_boleto_offset_vencimento_required_marker,1'],
            'boleto_valor' => ['required_if:tem_taxa,on', 'numeric'],
            'boleto_texto' => ['max:255'],
            'email_inscricaoaprovacao_texto' => ['max:255'],
            'email_inscricaorejeicao_texto' => ['max:255'],
            'email_matriculaaprovacao_texto' => ['max:255'],
            'email_matricularejeicao_texto' => ['max:255'],
        ];
    }

    public function messages() {
        return [
            'categoria_id.required' => 'A categoria é obrigatória!',
            'categoria_id.numeric' => 'A categoria é inválida!',
            'programa_id.required_unless' => 'O programa é obrigatório!',
            'descricao.max' => 'A descrição da seleção não pode exceder 255 caracteres!',
            'solicitacoesisencaotaxa_data_inicio.required_if' => 'A data de início das solicitações de isenção de taxa é obrigatória!',
            'solicitacoesisencaotaxa_hora_inicio.required_if' => 'A hora de início das solicitações de isenção de taxa é obrigatória!',
            'solicitacoesisencaotaxa_data_fim.required_if' => 'A data de fim das solicitações de isenção de taxa é obrigatória!',
            'solicitacoesisencaotaxa_hora_fim.required_if' => 'A hora de fim das solicitações de isenção de taxa é obrigatória!',
            'inscricoes_data_inicio.required_if' => 'A data de início das inscrições é obrigatória!',
            'inscricoes_hora_inicio.required_if' => 'A hora de início das inscrições é obrigatória!',
            'inscricoes_data_fim.required_if' => 'A data de fim das inscrições é obrigatória!',
            'inscricoes_hora_fim.required_if' => 'A hora de fim das inscrições é obrigatória!',
            'matriculas_data_inicio.required_if' => 'A data de início das matrículas é obrigatória!',
            'matriculas_hora_inicio.required_if' => 'A hora de início das matrículas é obrigatória!',
            'matriculas_data_fim.required_if' => 'A data de fim das matrículas é obrigatória!',
            'matriculas_hora_fim.required_if' => 'A hora de fim das matrículas é obrigatória!',
            'boleto_data_vencimento.required_if' => 'A data de vencimento do boleto é obrigatória!',
            'boleto_offset_vencimento.required_if' => 'A quantidade de dias úteis para pagamento do boleto é obrigatória!',
            'boleto_valor.required_if' => 'O valor do boleto é obrigatório!',
            'boleto_valor.numeric' => 'O valor do boleto é inválido!',
            'boleto_texto.max' => 'As eventuais informações adicionais no boleto não podem exceder 255 caracteres!',
            'email_inscricaoaprovacao_texto.max' => 'As eventuais informações adicionais no e-mail de aprovação da inscrição não podem exceder 255 caracteres!',
            'email_inscricaorejeicao_texto.max' => 'As eventuais informações adicionais no e-mail de rejeição da inscrição não podem exceder 255 caracteres!',
            'email_matriculaaprovacao_texto.max' => 'As eventuais informações adicionais no e-mail de aprovação da matrícula não podem exceder 255 caracteres!',
            'email_matricularejeicao_texto.max' => 'As eventuais informações adicionais no e-mail de rejeição da matrícula não podem exceder 255 caracteres!',
        ];
    }

    protected function prepareForValidation() {
        $selecao_temporaria = new Selecao($this->all());
        $this->merge([
            '_solicitacaoisencaotaxa_datas_required_marker' => ($this->input('tem_taxa') === 'on') ? 1 : 0,
            '_inscricao_datas_required_marker' => $selecao_temporaria->fazInscricoes() ? 1 : 0,
            '_matricula_datas_required_marker' => $selecao_temporaria->fazMatriculas() ? 1 : 0,
            'boleto_valor' => str_replace(',', '.', (string) $this->boleto_valor),
            '_boleto_data_vencimento_required_marker' => ($this->input('tem_taxa') === 'on' && $this->input('fluxo_continuo') !== 'on') ? 1 : 0,
            '_boleto_offset_vencimento_required_marker' => ($this->input('tem_taxa') === 'on' && $this->input('fluxo_continuo') === 'on') ? 1 : 0,
        ]);
    }
}
