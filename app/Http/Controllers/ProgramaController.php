<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProgramaRequest;
use App\Models\Nivel;
use App\Models\Programa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;

class ProgramaController extends Controller
{
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
        Gate::authorize('programas.viewAny');

        \UspTheme::activeUrl('programas');
        if (!$request->ajax())
            return view('programas.tree', $this->monta_compact_index());
    }

    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request   $request
     * @param  string                     $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, string $id)
    {
        Gate::authorize('programas.view', Programa::where('id', $id)->first());

        \UspTheme::activeUrl('programas');
        if ($request->ajax())
            return Programa::find((int) $id);    // preenche os dados do form de edição de um programa
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\ProgramaRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ProgramaRequest $request)
    {
        Gate::authorize('programas.create');

        $validator = Validator::make($request->all(), ProgramaRequest::rules, ProgramaRequest::messages);
        if ($validator->fails())
            return back()->withErrors($validator)->withInput();

        // transaction para não ter problema de inconsistência do DB
        DB::transaction(function () use ($request) {
            $programa = Programa::create($request->all());
            foreach (Nivel::all() as $nivel)    // adiciona relações deste programa com todos os níveis
                $programa->niveis()->attach($nivel);
        });

        $request->session()->flash('alert-success', 'Dados adicionados com sucesso');
        \UspTheme::activeUrl('programas');
        return redirect()->route('programas.index')->with($this->monta_compact_index());    // se fosse return view, um eventual F5 do usuário duplicaria o registro... POSTs devem ser com redirect
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\ProgramaRequest  $request
     * @param  string                              $id
     * @return \Illuminate\Http\Response
     */
    public function update(ProgramaRequest $request, string $id)
    {
        Gate::authorize('programas.update');

        $validator = Validator::make($request->all(), ProgramaRequest::rules, ProgramaRequest::messages);
        if ($validator->fails())
            return back()->withErrors($validator)->withInput();

        $programa = Programa::find((int) $id);
        $programa->fill($request->all());
        $programa->save();

        $request->session()->flash('alert-success', 'Dados editados com sucesso');
        \UspTheme::activeUrl('programas');
        return view('programas.tree', $this->monta_compact_index());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Http\Requests\ProgramaRequest  $request
     * @param  string                              $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(ProgramaRequest $request, string $id)
    {
        Gate::authorize('programas.delete');

        $programa = Programa::find((int) $id);
        if ($programa->selecoes()->exists())
            $request->session()->flash('alert-danger', 'Há seleções para este programa!');
        elseif ($programa->linhaspesquisa()->exists())
            $request->session()->flash('alert-danger', 'Há linhas de pesquisa/temas para este programa!');
        else {
            // transaction para não ter problema de inconsistência do DB
            DB::transaction(function () use ($programa) {
                if ($programa->niveis()->exists())
                    $programa->niveis()->detach();    // remove todas as relações com níveis deste programa
                $programa->delete();
            });

            $request->session()->flash('alert-success', 'Dados removidos com sucesso!');
        }
        \UspTheme::activeUrl('programas');
        return view('programas.tree', $this->monta_compact_index());
    }

    private function monta_compact_index()
    {
        $programas = Programa::all();
        $fields = Programa::getFields();
        $modal['url'] = 'programas';
        $modal['title'] = 'Editar Programa';
        $rules = ProgramaRequest::rules;

        return compact('programas', 'fields', 'modal', 'rules');
    }
}
