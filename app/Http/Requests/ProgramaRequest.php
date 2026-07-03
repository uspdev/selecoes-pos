<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProgramaRequest extends FormRequest
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
        'nome' => ['required', 'max:100'],
        'sigla' => ['required', 'max:3'],
        'descricao' => ['max:255'],
        'email_secretaria' => ['max:255', 'nullable', 'email'],
        'link_acompanhamento' => ['max:255', 'nullable', 'url', 'regex:/^(http:\/\/|https:\/\/)/'],
        'processos' => ['required', 'max:255'],
    ];

    public const messages = [
        'nome.required' => 'O nome do programa é obrigatório!',
        'nome.max' => 'O nome do programa não pode exceder 100 caracteres!',
        'sigla.required' => 'A sigla do programa é obrigatória!',
        'sigla.max' => 'A sigla do programa não pode exceder 3 caracteres!',
        'descricao.max' => 'A descrição do programa não pode exceder 255 caracteres!',
        'email_secretaria.max' => 'O e-mail da secretaria não pode exceder 255 caracteres!',
        'email_secretaria.email' => 'O e-mail da secretaria deve ser válido.',
        'link_acompanhamento.max' => 'O endereço no site da unidade para acompanhamento do processo pelos candidatos não pode exceder 255 caracteres!',
        'link_acompanhamento.url' => 'O endereço no site da unidade para acompanhamento do processo pelos candidatos deve ser uma URL válida.',
        'link_acompanhamento.regex' => 'O endereço no site da unidade para acompanhamento do processo pelos candidatos deve começar com http:// ou https://',
        'nome.required' => 'O nome do programa é obrigatório!',
        'nome.max' => 'O nome do programa não pode exceder 100 caracteres!',
        'processos.required' => 'O(s) processo(s) do programa é(são) obrigatório(s)!',
        'processos.max' => 'O(s) processo(s) do programa não pode(m) exceder 255 caracteres!',
    ];
}
