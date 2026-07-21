<?php

namespace App\Models;

use App\Observers\OrientadorObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Uspdev\Replicado\Pessoa;

class Orientador extends Model
{
    use HasFactory;

    # orientadores não segue convenção do laravel para nomes de tabela
    protected $table = 'orientadores';

    protected $fillable = [
        'codpes',
        'nome',
        'email',
        'externo'
    ];

    // uso no crud generico
    protected const fields = [
        [
            'name' => 'codpes',
            'label' => 'Orientador(a)',
        ],
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        Orientador::observe(OrientadorObserver::class);
    }

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

    public static function obterNome($codpes)
    {
        $orientador = self::where('codpes', $codpes)->first();
        if (!$orientador)
            return null;

        if ($orientador->externo)
            return $orientador->nome;
        else
            return Pessoa::obterNome($codpes);
    }

    public static function obterEmail($codpes)
    {
        $orientador = self::where('codpes', $codpes)->first();
        if (!$orientador)
            return null;

        if ($orientador->externo)
            return $orientador->email;
        else
            return Pessoa::email($codpes);
    }

    public static function listarOrientadores()
    {
        return self::get();
    }

    /**
     * relacionamento com linhas de pesquisa/temas
     */
    public function linhaspesquisa()
    {
        return $this->belongsToMany('App\Models\LinhaPesquisa', 'linhapesquisa_orientador', 'orientador_id', 'linhapesquisa_id')->withTimestamps();
    }

    /**
     * relacionamento com seleções
     */
    public function selecoes()
    {
        return $this->belongsToMany('App\Models\Selecao', 'selecao_orientador', 'orientador_id', 'selecao_id')->withTimestamps();
    }
}
