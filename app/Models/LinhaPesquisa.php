<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class LinhaPesquisa extends Model
{
    use HasFactory;

    # linhaspesquisa não segue convenção do laravel para nomes de tabela
    protected $table = 'linhaspesquisa';

    protected $fillable = [
        'nome',
        'programa_id',
    ];

    // uso no crud generico
    protected const fields = [
        [
            'name' => 'nome',
            'label' => 'Nome',
        ],
        [
            'name' => 'programa_id',
            'label' => 'Programa',
            'type' => 'select',
            'model' => 'Programa',
            'data' => [],
        ],
    ];

    // uso no crud generico
    public static function getFields()
    {
        $fields = self::fields;
        foreach ($fields as &$field) {
            if (substr($field['name'], -3) == '_id') {
                $class = '\\App\\Models\\' . $field['model'];
                $field['data'] = $class::allToSelect();
            }
        }
        return $fields;
    }

    /**
     * retorna todas as linhas de pesquisa/temas autorizados para o usuário
     * utilizado nas views common, para o select
     */
    public static function allToSelect()
    {
        $linhaspesquisa = self::get();
        $ret = [];
        foreach ($linhaspesquisa as $linhapesquisa)
            if (Gate::allows('linhaspesquisa.view', $linhapesquisa))
                $ret[$linhapesquisa->id] = $linhapesquisa->nome;
        return $ret;
    }

    /**
     * Menu Linhas de Pesquisa/Temas, lista as linhas de pesquisa/temas
     *
     * @return coleção de linhas de pesquisa/temas
     */
    public static function listarLinhasPesquisa()
    {
        return self::with('programa')
            ->whereIn('programa_id', \Auth::user()->listarProgramasGerenciados()->pluck('id'))    // linhas de pesquisa/temas de programas que o usuário gerencia, e também...
            ->orWhere(function ($query) {
                if (session('perfil') == 'admin')
                    $query->whereNotNull('id');
            })
            ->orderBy('programa_id')
            ->orderBy('id')
            ->get();
    }

    /**
     * Relacionamento: linha de pesquisa/tema pertence a programa
     */
    public function programa()
    {
        return $this->belongsTo('App\Models\Programa');
    }

    /**
     * relacionamento com níveis
     */
    public function niveis()
    {
        return $this->belongsToMany('App\Models\Nivel', 'nivel_linhapesquisa', 'linhapesquisa_id', 'nivel_id')->withTimestamps();
    }

    /**
     * relacionamento com combinações de níveis com linhas de pesquisa/temas
     */
    public function niveislinhaspesquisa()
    {
        return $this->hasMany('App\Models\NivelLinhaPesquisa', 'linhapesquisa_id');
    }

    /**
     * Accessor getter para selecoes
     */
    public function getSelecoesAttribute()
    {
        return $this->niveislinhaspesquisa->flatMap(function ($nivelLinhaPesquisa) {
            return $nivelLinhaPesquisa->selecoes;
        })->unique('id')->values();
    }
}
