<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ParametroRequest extends FormRequest
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
    public const rules = [
        'boleto_codigo_fonte_recurso' => ['required', 'integer'],
        'boleto_estrutura_hierarquica' => ['required', 'max:100'],
        'boleto_momento_envio' => ['required', 'max:100'],
        'link_inscricao_termos' => ['nullable', 'max:255', 'url', 'regex:/^(http:\/\/|https:\/\/)/'],
        'link_acompanhamento_especiais' => ['nullable', 'max:255', 'url', 'regex:/^(http:\/\/|https:\/\/)/'],
        'max_disciplinas_aluno_especial' => ['nullable', 'integer'],
        'email_servicoposgraduacao' => ['required', 'max:255', 'email'],
        'email_secaoinformatica' => ['nullable', 'max:255', 'email'],
        'email_gerenciamentosite' => ['nullable', 'max:255', 'email'],
    ];

    public const messages = [
        'boleto_codigo_fonte_recurso.required' => 'O código da fonte do recurso do boleto é obrigatório!',
        'boleto_codigo_fonte_recurso.integer' => 'O código da fonte do recurso do boleto é inválido!',
        'boleto_estrutura_hierarquica.required' => 'A estrutura hierárquica do boleto é obrigatória!',
        'boleto_estrutura_hierarquica.max' => 'A estrutura hierárquica do boleto não pode exceder 100 caracteres!',
        'boleto_momento_envio.required' => 'O momento de geração e envio do boleto é obrigatório!',
        'boleto_momento_envio.max' => 'O momento de geração e envio do boleto não pode exceder 100 caracteres!',
        'link_inscricao_termos.max' => 'O link para os termos de inscrição não pode exceder 255 caracteres!',
        'link_inscricao_termos.url' => 'O link para os termos de inscrição deve ser uma URL válida.',
        'link_inscricao_termos.regex' => 'O link para os termos de inscrição deve começar com http:// ou https://',
        'link_acompanhamento_especiais.max' => 'O endereço no site da unidade para acompanhamento do processo pelos candidatos a aluno especial não pode exceder 255 caracteres!',
        'link_acompanhamento_especiais.url' => 'O endereço no site da unidade para acompanhamento do processo pelos candidatos a aluno especial deve ser uma URL válida.',
        'link_acompanhamento_especiais.regex' => 'O endereço no site da unidade para acompanhamento do processo pelos candidatos a aluno especial deve começar com http:// ou https://',
        'max_disciplinas_aluno_especial.integer' => 'O número máximo de disciplinas para alunos especiais é inválido!',
        'email_servicoposgraduacao.required' => 'O e-mail do serviço de pós-graduação é obrigatório!',
        'email_servicoposgraduacao.max' => 'O e-mail do serviço de pós-graduação não pode exceder 255 caracteres!',
        'email_servicoposgraduacao.email' => 'O e-mail do serviço de pós-graduação deve ser válido.',
        'email_secaoinformatica.max' => 'O e-mail da seção de informática não pode exceder 255 caracteres!',
        'email_secaoinformatica.email' => 'O e-mail da seção de informática deve ser válido.',
        'email_gerenciamentosite.max' => 'O e-mail da equipe de gerenciamento do site não pode exceder 255 caracteres!',
        'email_gerenciamentosite.email' => 'O e-mail da equipe de gerenciamento do site deve ser válido.',
    ];
}
