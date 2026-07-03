<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class Programa extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome',
        'sigla',
        'descricao',
        'email_secretaria',
        'link_acompanhamento',
        'processos',
    ];

    // uso no crud generico
    protected const fields = [
        [
            'name' => 'nome',
            'label' => 'Nome',
        ],
        [
            'name' => 'sigla',
            'label' => 'Sigla',
        ],
        [
            'name' => 'descricao',
            'label' => 'Descrição',
        ],
        [
            'name' => 'email_secretaria',
            'label' => 'E-mail da Secretaria',
        ],
        [
            'name' => 'link_acompanhamento',
            'label' => 'Endereço no Site da Unidade para Acompanhamento do Processo pelos Candidatos',
        ],
        [
            'name' => 'processos',
            'label' => 'Processo(s) Utilizado(s)',
            'type' => 'select',
            'data' => ['Inscrição' => 'Inscrição', 'Matrícula' => 'Matrícula'],
        ],
    ];

    // uso no crud generico
    public static function getFields()
    {
        return self::fields;
    }

    /**
     * retorna todos os programas
     * utilizado nas views common, para o select
     */
    public static function allToSelect()
    {
        $programas = self::get();
        $ret = [];
        foreach ($programas as $programa)
            if (Gate::allows('programas.view', $programa)) {
                $ret[$programa->id] = $programa->nomeCompleto();
            }
        return $ret;
    }

    public function obterResponsaveis()
    {
        return [
            [
                'funcao' => 'Docentes do Programa',
                'users' => $this->users()->wherePivot('funcao', 'Docentes do Programa')->orderBy('id')->get(),
            ],
            [
                'funcao' => 'Secretários(as) do Programa',
                'users' => $this->users()->wherePivot('funcao', 'Secretários(as) do Programa')->orderBy('id')->get(),
            ],
            [
                'funcao' => 'Coordenadores do Programa',
                'users' => $this->users()->wherePivot('funcao', 'Coordenadores do Programa')->orderBy('id')->get(),
            ],
            [
                'funcao' => 'Serviço de Pós-Graduação',
                'users' => DB::table('user_programa')->join('users', 'user_programa.user_id', '=', 'users.id')->where('user_programa.funcao', 'Serviço de Pós-Graduação')->orderBy('user_programa.id')->get(),    // não dá pra partir de Programa::, pelo fato de programa_id ser null na tabela relacional
            ],
            [
                'funcao' => 'Coordenadores da Pós-Graduação',
                'users' => DB::table('user_programa')->join('users', 'user_programa.user_id', '=', 'users.id')->where('user_programa.funcao', 'Coordenadores da Pós-Graduação')->orderBy('user_programa.id')->get(),    // não dá pra partir de Programa::, pelo fato de programa_id ser null na tabela relacional
            ],
        ];
    }

    /**
     * Accessor getter para inscricao
     */
    public function getInscricaoAttribute()
    {
        return str_contains($this->processos, 'Inscrição');
    }

    /**
     * Accessor getter para matricula
     */
    public function getMatriculaAttribute()
    {
        return str_contains($this->processos, 'Matrícula');
    }

    /**
     * Programa possui seleções
     */
    public function selecoes()
    {
        return $this->hasMany('App\Models\Selecao');
    }

    /**
     * Programa possui linhas de pesquisa/temas
     */
    public function linhaspesquisa()
    {
        return $this->hasMany('App\Models\LinhaPesquisa');
    }

    /**
     * relacionamento com níveis
     */
    public function niveis()
    {
        return $this->belongsToMany('App\Models\Nivel', 'nivel_programa', 'programa_id', 'nivel_id')->withTimestamps();
    }

    /**
     * relacionamento com users
     */
    public function users()
    {
        return $this->belongsToMany('App\Models\User', 'user_programa')->withPivot('funcao')->withTimestamps();
    }


    public function parametro()
    {
        return $this->belongsTo(Parametro::class, 'parametro_id');
    }

    public function getParametro()
    {
        // se tem um único parâmetro no sistema, retorna esse mesmo.
        if(config('selecoes-pos.usar_parametro_unico')) {
            return Parametro::first();
        }

        // se possui múltiplos parâmetros, retorna aquele que está relacionado a esse programa.
        return $this->parametro;
    }

    public function nomeCompleto()
    {
        return $this->nome . ' (' . $this->sigla . ')';
    }
}
