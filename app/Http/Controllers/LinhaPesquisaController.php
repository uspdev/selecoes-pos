<?php

namespace App\Http\Controllers;

use App\Http\Requests\LinhaPesquisaRequest;
use App\Models\LinhaPesquisa;
use App\Models\Nivel;
use App\Models\Orientador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;

class LinhaPesquisaController extends Controller
{
    // crud generico
    public static $data = [
        'title' => 'Linhas de Pesquisa/Temas',
        'url' => 'linhaspesquisa',     // caminho da rota do resource
        'modal' => true,
        'showId' => false,
        'viewBtn' => true,
        'editBtn' => false,
        'model' => 'App\Models\LinhaPesquisa',
    ];

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request   $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        Gate::authorize('linhaspesquisa.viewAny');

        \UspTheme::activeUrl('linhaspesquisa');
        if (!$request->ajax())
            return view('linhaspesquisa.tree', $this->monta_compact_index());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        Gate::authorize('linhaspesquisa.create');

        \UspTheme::activeUrl('linhaspesquisa');
        return view('linhaspesquisa.edit', $this->monta_compact(new LinhaPesquisa, 'create'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\LinhaPesquisaRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(LinhaPesquisaRequest $request)
    {
        Gate::authorize('linhaspesquisa.create');

        $validator = Validator::make($request->all(), LinhaPesquisaRequest::rules, LinhaPesquisaRequest::messages);
        if ($validator->fails()) {
            \UspTheme::activeUrl('linhaspesquisa');
            return back()->withErrors($validator)->withInput();
        }

        // transaction para não ter problema de inconsistência do DB
        $linhapesquisa = DB::transaction(function () use ($request) {
            $linhapesquisa = LinhaPesquisa::create($request->all());
            foreach (Nivel::all() as $nivel)    // adiciona relações desta linha de pesquisa/tema com todos os níveis
                $linhapesquisa->niveis()->attach($nivel);
            return $linhapesquisa;
        });

        $request->session()->flash('alert-success', 'Linha de pesquisa/tema cadastrado com sucesso');
        \UspTheme::activeUrl('linhaspesquisa');
        return redirect()->to(url('linhaspesquisa/edit/' . $linhapesquisa->id))->with($this->monta_compact($linhapesquisa, 'edit'));    // se fosse return view, um eventual F5 do usuário duplicaria o registro... POSTs devem ser com redirect
    }

    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request   $request
     * @param  \App\Models\LinhaPesquisa  $linhapesquisa
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, LinhaPesquisa $linhapesquisa)
    {
        Gate::authorize('linhaspesquisa.update', $linhapesquisa);

        \UspTheme::activeUrl('linhaspesquisa');
        return view('linhaspesquisa.edit', $this->monta_compact($linhapesquisa, 'edit'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\LinhaPesquisaRequest  $request
     * @param  \App\Models\LinhaPesquisa                $linhapesquisa
     * @return \Illuminate\Http\Response
     */
    public function update(LinhaPesquisaRequest $request, LinhaPesquisa $linhapesquisa)
    {
        Gate::authorize('linhaspesquisa.update', $linhapesquisa);

        $validator = Validator::make($request->all(), LinhaPesquisaRequest::rules, LinhaPesquisaRequest::messages);
        if ($validator->fails()) {
            \UspTheme::activeUrl('linhaspesquisa');
            return view('linhaspesquisa.edit', $this->monta_compact($linhapesquisa, 'edit'))->withErrors($validator);    // preciso especificar 'edit'... se eu fizesse um return back(), e o usuário estivesse vindo de um update após um create, a variável $modo voltaria a ser 'create', e a página ficaria errada
        }

        $linhapesquisa->nome = $request->nome;
        $linhapesquisa->programa_id = $request->programa_id;
        $linhapesquisa->save();

        $request->session()->flash('alert-success', 'Linha de pesquisa/tema alterado com sucesso');
        \UspTheme::activeUrl('linhaspesquisa');
        return view('linhaspesquisa.edit', $this->monta_compact($linhapesquisa, 'edit'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Http\Requests\LinhaPesquisaRequest  $request
     * @param  string                                   $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(LinhaPesquisaRequest $request, string $id)
    {
        $linhapesquisa = LinhaPesquisa::find((int) $id);
        Gate::authorize('linhaspesquisa.delete', $linhapesquisa);

        if ($linhapesquisa->selecoes->isNotEmpty())
            $request->session()->flash('alert-danger', 'Há seleções para esta linha de pesquisa/tema!');
        else {
            // transaction para não ter problema de inconsistência do DB
            DB::transaction(function () use ($linhapesquisa) {
                if ($linhapesquisa->niveis()->exists())
                    $linhapesquisa->niveis()->detach();    // remove todas as relações com níveis desta linha de pesquisa/tema
                $linhapesquisa->delete();
            });

            $request->session()->flash('alert-success', 'Dados removidos com sucesso!');
        }
        \UspTheme::activeUrl('linhaspesquisa');
        return view('linhaspesquisa.tree', $this->monta_compact_index());
    }

    /**
     * Adicionar orientadores relacionados à linha de pesquisa/tema
     * autorizado a qualquer um que tenha acesso à linha de pesquisa/tema
     * request->codpes = required, int
     */
    public function storeOrientador(Request $request, LinhaPesquisa $linhapesquisa)
    {
        Gate::authorize('linhaspesquisa.update', $linhapesquisa);

        $request->validate([
            'id' => 'required',
        ],
        [
            'id.required' => 'Orientador(a) obrigatório(a)',
        ]);

        // transaction para não ter problema de inconsistência do DB
        $db_transaction = DB::transaction(function () use ($request, $linhapesquisa) {

            $orientador = Orientador::where('id', $request->id)->first();

            $existia = $linhapesquisa->orientadores()->detach($orientador);

            $linhapesquisa->orientadores()->attach($orientador);

            return ['orientador' => $orientador, 'existia' => $existia];
        });

        if (!$db_transaction['existia'])
            $request->session()->flash('alert-success', 'O(A) orientador(a) ' . Orientador::obterNome($db_transaction['orientador']->codpes) . ' foi adicionado(a) à essa linha de pesquisa/tema');
        else
            $request->session()->flash('alert-info', 'O(A) orientador(a) ' . Orientador::obterNome($db_transaction['orientador']->codpes) . ' já estava vinculado(a) à essa linha de pesquisa/tema');
        \UspTheme::activeUrl('linhaspesquisa');
        return redirect()->to(url('linhaspesquisa/edit/' . $linhapesquisa->id))->with($this->monta_compact($linhapesquisa, 'edit'));    // se fosse return view, um eventual F5 do usuário duplicaria o registro... POSTs devem ser com redirect
    }

    /**
     * Remove orientadores relacionados à linha de pesquisa/tema
     * $user = required
     */
    public function destroyOrientador(Request $request, LinhaPesquisa $linhapesquisa, Orientador $orientador)
    {
        Gate::authorize('linhaspesquisa.update', $linhapesquisa);

        $linhapesquisa->orientadores()->detach($orientador);

        $request->session()->flash('alert-success', 'O(A) orientador(a) ' . Orientador::obterNome($orientador->codpes) . ' foi removido(a) dessa linha de pesquisa/tema');
        \UspTheme::activeUrl('linhaspesquisa');
        return view('linhaspesquisa.edit', $this->monta_compact($linhapesquisa, 'edit'));
    }

    private function monta_compact_index()
    {
        $linhaspesquisa = LinhaPesquisa::listarLinhasPesquisa();
        $fields = LinhaPesquisa::getFields();
        $modal_pessoa['url'] = 'linhas de pesquisa/temas';
        $modal_pessoa['title'] = 'Adicionar Pessoa';
        $modal['url'] = 'linhaspesquisa';
        $modal['title'] = 'Editar Linha de Pesquisa/Tema';
        $rules = LinhaPesquisaRequest::rules;

        return compact('linhaspesquisa', 'fields', 'modal', 'modal_pessoa', 'rules');
    }

    private function monta_compact(LinhaPesquisa $linhapesquisa, string $modo)
    {
        $data = (object) self::$data;
        if (!is_null($linhapesquisa) && !is_null($linhapesquisa->orientadores))
            foreach ($linhapesquisa->orientadores as $orientador)
                $orientador->nome = Orientador::obterNome($orientador->codpes);
        $objeto = $linhapesquisa;
        $fields_orientador = Orientador::getFields();
        $orientadores = Orientador::listarOrientadores();
        $rules = LinhaPesquisaRequest::rules;

        return compact('data', 'objeto', 'fields_orientador', 'orientadores', 'rules', 'modo');
    }
}
