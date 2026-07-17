<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class Categoria extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome',
        'descricao',
    ];

    // uso no crud generico
    protected const fields = [
        [
            'name' => 'nome',
            'label' => 'Nome',
        ],
        [
            'name' => 'descricao',
            'label' => 'Descrição',
        ],
    ];

    // uso no crud generico
    public static function getFields()
    {
        return self::fields;
    }

    /**
     * retorna todas as categorias
     * utilizado nas views common, para o select
     */
    public static function allToSelect()
    {
        $categorias = self::get();
        $ret = [];
        foreach ($categorias as $categoria)
            if (Gate::allows('categorias.view', $categoria))
                $ret[$categoria->id] = $categoria->nome;
        return $ret;
    }

    public function exigeNivel()
    {
        return ($this->nome == 'Aluno Regular');
    }

    public function exigeLinhaPesquisa()
    {
        return ($this->nome == 'Aluno Regular');
    }

    public function exigeDisciplinas()
    {
        return ($this->nome == 'Aluno Especial');
    }

    /**
     * Categoria possui seleções
     */
    public function selecoes()
    {
        return $this->hasMany('App\Models\Selecao');
    }

    /**
     * Relacionamento n:n com user, atributo funcao: Gerente
     */
    public function users()
    {
        return $this->belongsToMany('App\Models\User', 'user_categoria')
            ->orderBy('users.name')
            ->withTimestamps();
    }

    /**
     * relacionamento com tipos de arquivo
     */
    public function tiposarquivo()
    {
        return $this->belongsToMany('App\Models\TipoArquivo', 'tipoarquivo_categoria', 'categoria_id', 'tipoarquivo_id')->withTimestamps();
    }
}
