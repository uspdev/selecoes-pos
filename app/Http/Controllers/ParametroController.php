<?php

namespace App\Http\Controllers;

use App\Http\Requests\ParametroRequest;
use App\Models\Parametro;
use App\Models\Programa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;

class ParametroController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit($id = null)
    {
        Gate::authorize('parametros.update');
        \UspTheme::activeUrl('parametros');

        if (config('selecoes-pos.usar_parametro_unico'))
            return view('parametros.edit', $this->monta_compact($id));

        if (request()->has('programa_id')) {
            $prog_id = request()->query('programa_id');
            $programa = Programa::find($prog_id);
            $id_param = ($programa && $programa->parametro_id) ? $programa->parametro_id : null;
            return view('parametros.edit', $this->monta_compact($id_param, $prog_id));
        }

        $programas = Programa::with('parametro')->get();
        return view('parametros.index', compact('programas'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\ParametroRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function update(ParametroRequest $request)
    {
        Gate::authorize('parametros.update');

        $validator = Validator::make($request->all(), ParametroRequest::rules, ParametroRequest::messages);
        if ($validator->fails())
            return back()->withErrors($validator)->withInput();

        $id_global = Parametro::orderBy('id', 'asc')->first()->id ?? null;
        if (!config('selecoes-pos.usar_parametro_unico') && $request->filled('programa_id')) {
            $programa = Programa::find($request->programa_id);
            if (!$programa->parametro_id || $programa->parametro_id == $id_global)
                $parametro = new Parametro;
            else
                $parametro = Parametro::find($programa->parametro_id);
        } else
            $parametro = Parametro::first() ?: new Parametro;

        // atribuição manual dos dados
        $parametro->boleto_codigo_fonte_recurso = $request->boleto_codigo_fonte_recurso;
        $parametro->boleto_estrutura_hierarquica = $request->boleto_estrutura_hierarquica;
        $parametro->boleto_momento_envio = $request->boleto_momento_envio;
        $parametro->link_inscricao_termos = $request->link_inscricao_termos;
        $parametro->link_acompanhamento_especiais = $request->link_acompanhamento_especiais;
        $parametro->processos_especiais = $request->processos_especiais;
        $parametro->max_disciplinas_aluno_especial = $request->max_disciplinas_aluno_especial;
        $parametro->email_servicoposgraduacao = $request->email_servicoposgraduacao;
        $parametro->email_secaoinformatica = $request->email_secaoinformatica;
        $parametro->email_gerenciamentosite = $request->email_gerenciamentosite;
        $parametro->save();

        if (isset($programa)) {
            $programa->parametro_id = $parametro->id;
            $programa->save();
        }

        $request->session()->flash('alert-success', 'Dados salvos com sucesso');

        if (!config('selecoes-pos.usar_parametro_unico'))
            return redirect()->route('parametros.edit');    // retorna para o index/tabela

        \UspTheme::activeUrl('parametros');
        return view('parametros.edit', $this->monta_compact($parametro->id));
    }

   private function monta_compact($id = null, $programa_id = null)
    {
        // carrega o registro correto ou um novo se for a primeira customização do programa
        $parametros = $id ? Parametro::find($id) : (Parametro::first() ?: new Parametro);
        $fields = Parametro::getFields();
        $rules = ParametroRequest::rules;
        $programasParaSelect = !config('selecoes-pos.usar_parametro_unico') ? Programa::all() : collect();

        return compact('parametros', 'fields', 'rules', 'programasParaSelect', 'programa_id');
    }
}
