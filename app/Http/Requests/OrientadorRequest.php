<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrientadorRequest extends FormRequest
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
        'codpes' => ['required_without:externo', 'nullable'],
        'externo_nome' => ['required_if:externo,on', 'max:100'],
        'externo_codpes' => ['required_if:externo,on', 'nullable', 'integer'],
        'externo_email' => ['required_if:externo,on', 'nullable', 'email'],
    ];

    public const messages = [
        'codpes.required_without' => 'O(A) orientador(a) é obrigatório(a)!',
        'externo_nome.required_if' => 'O nome do(a) orientador(a) é obrigatório!',
        'externo_nome.max' => 'O nome do(a) orientador(a) não pode exceder 100 caracteres!',
        'externo_codpes.required_if' => 'O número USP do(a) orientador(a) é obrigatório!',
        'externo_codpes.integer' => 'O número USP do(a) orientador(a) deve ser um número inteiro!',
        'externo_email.required_if' => 'O e-mail do(a) orientador(a) é obrigatório!',
        'externo_email.email' => 'O e-mail do(a) orientador(a) é inválido!',
    ];
}
