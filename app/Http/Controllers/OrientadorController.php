<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrientadorRequest;
use App\Models\Orientador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;

class OrientadorController extends Controller
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
        Gate::authorize('orientadores.viewAny');

        \UspTheme::activeUrl('orientadores');
        if (!$request->ajax())
            return view('orientadores.tree', $this->monta_compact_index());
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
        Gate::authorize('orientadores.view');

        \UspTheme::activeUrl('orientadores');
        if ($request->ajax())
            return Orientador::find((int) $id);    // preenche os dados do form de edição de um orientador
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\OrientadorRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(OrientadorRequest $request)
    {
        Gate::authorize('orientadores.create');

        $validator = Validator::make($request->all(), OrientadorRequest::rules, OrientadorRequest::messages);
        if ($validator->fails())
            return back()->withErrors($validator)->withInput();

        $request->merge(['externo' => $request->has('externo')]);    // acerta o valor do campo "externo" (pois, se o usuário deixou false, o campo não vem no $request e, se o usuário deixou true, ele vem mas com valor null)

        $requestData = $request->all();
        if ($requestData['externo']) {
            $requestData['nome'] = $requestData['externo_nome'];
            $requestData['codpes'] = $requestData['externo_codpes'];
            $requestData['email'] = $requestData['externo_email'];
        }

        $orientador = Orientador::create($requestData);

        $request->session()->flash('alert-success', 'Dados adicionados com sucesso');
        \UspTheme::activeUrl('orientadores');
        return redirect()->route('orientadores.index')->with($this->monta_compact_index());    // se fosse return view, um eventual F5 do usuário duplicaria o registro... POSTs devem ser com redirect
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\OrientadorRequest  $request
     * @param  string                                $id
     * @return \Illuminate\Http\Response
     */
    public function update(OrientadorRequest $request, string $id)
    {
        Gate::authorize('orientadores.update');

        $validator = Validator::make($request->all(), OrientadorRequest::rules, OrientadorRequest::messages);
        if ($validator->fails())
            return back()->withErrors($validator)->withInput();

        $request->merge(['externo' => $request->has('externo')]);    // acerta o valor do campo "externo" (pois, se o usuário deixou false, o campo não vem no $request e, se o usuário deixou true, ele vem mas com valor null)

        $requestData = $request->all();
        if ($requestData['externo']) {
            $requestData['nome'] = $requestData['externo_nome'];
            $requestData['codpes'] = $requestData['externo_codpes'];
            $requestData['email'] = $requestData['externo_email'];
        }

        $orientador = Orientador::find((int) $id);
        $orientador->fill($requestData);
        $orientador->save();

        $request->session()->flash('alert-success', 'Dados editados com sucesso');
        \UspTheme::activeUrl('orientadores');
        return view('orientadores.tree', $this->monta_compact_index());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Http\Requests\OrientadorRequest  $request
     * @param  string                                $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(OrientadorRequest $request, string $id)
    {
        Gate::authorize('orientadores.delete');

        $orientador = Orientador::find((int) $id);
        if ($orientador->selecoes()->exists())
            $request->session()->flash('alert-danger', 'Há seleções associadas a este(a) orientador(a)!');
        else {
            $orientador->linhaspesquisa()->detach();
            $orientador->delete();
            $request->session()->flash('alert-success', 'Dados removidos com sucesso!');
        }

        \UspTheme::activeUrl('orientador');
        return view('orientadores.tree', $this->monta_compact_index());
    }

    private function monta_compact_index()
    {
        $orientadores = Orientador::all();
        $fields = Orientador::getFields();
        $modal['url'] = 'orientadores';
        $modal['title'] = 'Editar Orientador(a)';
        $rules = OrientadorRequest::rules;

        return compact('orientadores', 'fields', 'modal', 'rules');
    }
}
