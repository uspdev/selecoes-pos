<?php

namespace App\Http\Controllers;

use App\Models\Programa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;

class FuncaoController extends Controller
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
    public function edit()
    {
        Gate::authorize('funcoes.update');

        \UspTheme::activeUrl('funcoes');
        return view('funcoes.edit', $this->monta_compact());
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        if ($add = $request->add_codpes) {

            // transaction para não ter problema de inconsistência do DB
            DB::transaction(function () use ($request, $add) {

                $user = User::findOrCreateFromReplicado($add);
                if ($user)
                    $user->associarProgramaFuncao($request->programa, $request->funcao);
            });
        }

        if ($rem = $request->rem_codpes) {
            $user = User::where('codpes', $rem)->first();
            if ($user)
                $user->desassociarProgramaFuncao($request->programa, $request->funcao);
        }

        $request->session()->flash('alert-success', 'Dados editados com sucesso');
        \UspTheme::activeUrl('funcoes');
        return view('funcoes.edit', $this->monta_compact());
    }

    private function monta_compact()
    {
        $programas_docentes = Programa::with(['users' => function ($query) {
            $query->where('funcao', 'Docentes do Programa')
                  ->orderBy('user_programa.programa_id')
                  ->orderBy('users.name');
        }])->get();
        $programas_secretarios = Programa::with(['users' => function ($query) {
            $query->where('funcao', 'Secretários(as) do Programa')
                  ->orderBy('user_programa.programa_id')
                  ->orderBy('users.name');
        }])->get();
        $programas_coordenadores = Programa::with(['users' => function ($query) {
            $query->where('funcao', 'Coordenadores(as) do Programa')
                  ->orderBy('user_programa.programa_id')
                  ->orderBy('users.name');
        }])->get();
        $posgraduacao_servico_users = DB::table('user_programa')    // não dá pra partir de User:: nem Programa::, pelo fato de programa_id ser null na tabela relacional
            ->join('users', 'user_programa.user_id', '=', 'users.id')
            ->where('user_programa.funcao', 'Serviço de Pós-Graduação')
            ->orderBy('users.name')
            ->select('users.name', 'users.codpes')
            ->get()
            ->map(function ($user) {
                return (object) [
                    'name' => $user->name,
                    'codpes' => $user->codpes,
                ];
            })->values()->toArray();
        $posgraduacao_coordenadores_users = DB::table('user_programa')    // não dá pra partir de User:: nem Programa::, pelo fato de programa_id ser null na tabela relacional
            ->join('users', 'user_programa.user_id', '=', 'users.id')
            ->where('user_programa.funcao', 'Coordenadores(as) da Pós-Graduação')
            ->orderBy('users.name')
            ->select('users.name', 'users.codpes')
            ->get()
            ->map(function ($user) {
                return (object) [
                    'name' => $user->name,
                    'codpes' => $user->codpes,
                ];
            })->values()->toArray();

        return compact('programas_docentes', 'programas_secretarios', 'programas_coordenadores', 'posgraduacao_servico_users', 'posgraduacao_coordenadores_users');
    }
}
