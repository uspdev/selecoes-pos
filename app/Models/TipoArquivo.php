<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class TipoArquivo extends Model
{
    use HasFactory;

    # tiposarquivo não segue convenção do laravel para nomes de tabela
    protected $table = 'tiposarquivo';

    protected $fillable = [
        'classe_nome',
        'nome',
        'abreviacao',
        'obrigatorio',
        'obrigatorio_condicao_campo',
        'obrigatorio_condicao_valor',
        'minimo',
    ];

    // uso no crud generico
    protected const fields = [
        [
            'name' => 'classe_nome',
            'label' => 'Para',
            'type' => 'select',
            'data' => ['Seleções' => 'Seleções', 'Solicitações de Isenção de Taxa' => 'Solicitações de Isenção de Taxa', 'Inscrições' => 'Inscrições', 'Matrículas' => 'Matrículas'],    // repete chave e valor, para que no select os values das options sejam também o texto
        ],
        [
            'name' => 'nome',
            'label' => 'Nome',
        ],
        [
            'name' => 'abreviacao',
            'label' => 'Abreviação',
        ],
        [
            'name' => 'obrigatorio',
            'label' => 'Obrigatório?',
            'type' => 'select',
            'data' => ['Sim' => 'Sim', 'Condicional' => 'Condicional', 'Não' => 'Não'],
        ],
        [
            'name' => 'obrigatorio_condicao_campo',
            'label' => 'Campo',
        ],
        [
            'name' => 'obrigatorio_condicao_valor',
            'label' => 'Valor',
        ],
        [
            'name' => 'minimo',
            'label' => 'Mínimo',
            'type' => 'integer',
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
     * retorna todos os tipos de arquivo
     * utilizado nas views common, para o select
     */
    public static function allToSelect()
    {
        $tiposarquivo = self::get();
        $ret = [];
        foreach ($tiposarquivo as $tipoarquivo)
            if (Gate::allows('tiposarquivo.view', $linhapesquisa))
                $ret[$tipoarquivo->id] = $tipoarquivo->nome;
        return $ret;
    }

    /**
     * MUTATOR: intercepta antes de salvar no banco
     * se vier 'Sim' ou 'Condicional', grava 1; senão, grava 0
     */
    public function setObrigatorioAttribute($value)
    {
        $this->attributes['obrigatorio'] = (($value === 'Sim') || ($value === 'Condicional')) ? 1 : 0;
    }

    /**
     * Accessor getter para Obrigatorio
     * intercepta quando lê do banco para a tela
     * se for 1 e o campo de condição estiver preenchido, devolve 'Condicional' para o <select> marcar certo
     */
    public function getObrigatorioAttribute($value)
    {
        if ($value == 1)
            return !empty($this->obrigatorio_condicao_campo) ? 'Condicional' : 'Sim';

        return 'Não';
    }

    /**
     * Verifica se o tipo de arquivo é obrigatório, considerando tanto a obrigatoriedade incondicional quanto uma eventual obrigatoriedade condicional (baseada no JSON do campo extras)
     */
    public function isObrigatorio($extras)
    {
        switch ($this->obrigatorio) {
            case 'Sim':
                return true;

            case 'Condicional':
                $extras = array_change_key_case(json_decode($extras, true) ?? [], CASE_LOWER);
                $obrigatorio_condicao_campo = mb_strtolower($this->obrigatorio_condicao_campo, 'UTF-8');
                $obrigatorio_condicao_valor = mb_strtolower($this->obrigatorio_condicao_valor, 'UTF-8');

                return (isset($extras[$obrigatorio_condicao_campo]) &&
                        (mb_strtolower($extras[$obrigatorio_condicao_campo], 'UTF-8') == $obrigatorio_condicao_valor));    // retorna se satisfaz a condição de obrigatoriedade condicional

            default:
                return false;
        }
    }

    /**
     * Retorna os tipos de arquivo obrigatórios para um determinado objeto, considerando tanto a obrigatoriedade incondicional quanto uma eventual obrigatoriedade condicional (baseada no JSON do campo extras)
     */
    public static function obterTiposArquivoObrigatorios(object $objeto, string $classe_nome)
    {
        $extras = array_change_key_case(json_decode($objeto->extras, true) ?? [], CASE_LOWER);

        return $objeto->selecao->tiposarquivo()->where('classe_nome', $classe_nome)->where('obrigatorio', 1)->get()
            ->filter(function ($tipo) use ($extras) {
                if (empty($tipo->obrigatorio_condicao_campo))
                    return true;    // se não foi definido campo de condicionalidade da obrigatoriedade, retorna todos os obrigatórios

                $obrigatorio_condicao_campo = mb_strtolower($tipo->obrigatorio_condicao_campo, 'UTF-8');
                $obrigatorio_condicao_valor = mb_strtolower($tipo->obrigatorio_condicao_valor, 'UTF-8');
                return (isset($extras[$obrigatorio_condicao_campo]) &&
                        (mb_strtolower($extras[$obrigatorio_condicao_campo], 'UTF-8') == $obrigatorio_condicao_valor));    // retorna os que satisfazem a condição de obrigatoriedade condicional

            })->values();
    }

    public static function obterTiposArquivoPossiveis(string $classe_nome, $niveis, ?int $programa_id)
    {
        switch ($classe_nome) {
            case 'Selecao':
                // todos os tipos de arquivo possíveis para seleções
                return self::where('classe_nome', 'Seleções')->get();

            case 'SolicitacaoIsencaoTaxa':
                // todos os tipos de arquivo possíveis para solicitações de isenção de taxa
                return self::where('classe_nome', 'Solicitações de Isenção de Taxa')->get();

            case 'Inscricao':
                // todos os tipos de arquivo possíveis para inscrições
                return self::where('classe_nome', 'Inscrições')->where(function ($query) use ($niveis, $programa_id) {
                    if ($niveis->isEmpty())
                        $query->whereHas('categorias', function ($query) { $query->where('nome', 'Aluno Especial'); });
                    else
                        // se houver combinação de nível com programa, se restringe a ela
                        $query->whereHas('niveisprogramas', function ($query) use ($niveis, $programa_id) {
                            $query->whereIn('nivel_id', function ($query) use ($niveis) {
                                $query->select('id')->from('niveis')->whereIn('nome', $niveis->pluck('nome'));
                            })->where('programa_id', $programa_id);
                        })->whereHas('categorias', function ($query) { $query->where('nome', 'Aluno Regular'); });
                })->get();

            case 'Matricula':
                // todos os tipos de arquivo possíveis para matrículas
                return self::where('classe_nome', 'Matrículas')->where(function ($query) use ($niveis, $programa_id) {
                    if ($niveis->isEmpty())
                        $query->whereHas('categorias', function ($query) { $query->where('nome', 'Aluno Especial'); });
                    else
                        // se houver combinação de nível com programa, se restringe a ela
                        $query->whereHas('niveisprogramas', function ($query) use ($niveis, $programa_id) {
                            $query->whereIn('nivel_id', function ($query) use ($niveis) {
                                $query->select('id')->from('niveis')->whereIn('nome', $niveis->pluck('nome'));
                            })->where('programa_id', $programa_id);
                        })->whereHas('categorias', function ($query) { $query->where('nome', 'Aluno Regular'); });
                })->get();
        }
    }

    public static function obterTiposArquivoDaSelecao(string $classe_nome, $niveis, Selecao $selecao)
    {
        $programa_id = $selecao->programa_id;
        switch ($classe_nome) {
            case 'SolicitacaoIsencaoTaxa':
                // todos os tipos de arquivo para solicitações de isenção de taxa nesta seleção
                return $selecao->tiposarquivo()->where('classe_nome', 'Solicitações de Isenção de Taxa')->get();

            case 'Inscricao':
                // todos os tipos de arquivo para inscrições nesta seleção
                return $selecao->tiposarquivo()->where('classe_nome', 'Inscrições')->where(function ($query) use ($niveis, $programa_id) {
                    if (!$niveis->isEmpty())
                        // se houver combinação de nível com programa, se restringe a ela
                        $query->whereHas('niveisprogramas', function ($query) use ($niveis, $programa_id) {
                            $query->whereIn('nivel_id', function ($query) use ($niveis) {
                                $query->select('id')->from('niveis')->whereIn('nome', $niveis->pluck('nome'));
                            })->where('programa_id', $programa_id);
                        });
                })->get();

            case 'Matricula':
                // todos os tipos de arquivo para matrículas nesta seleção
                return $selecao->tiposarquivo()->where('classe_nome', 'Matrículas')->where(function ($query) use ($niveis, $programa_id) {
                    if (!$niveis->isEmpty())
                        // se houver combinação de nível com programa, se restringe a ela
                        $query->whereHas('niveisprogramas', function ($query) use ($niveis, $programa_id) {
                            $query->whereIn('nivel_id', function ($query) use ($niveis) {
                                $query->select('id')->from('niveis')->whereIn('nome', $niveis->pluck('nome'));
                            })->where('programa_id', $programa_id);
                        });
                })->get();
        }
    }

    /**
     * Lista os tipos de arquivo autorizados para o usuário
     */
    public static function listarTiposArquivo()
    {
        if (!in_array(session('perfil'), ['gerente', 'docente']))
                return self::query();

        if (DB::table('user_programa')    // não dá pra partir de $this->, pelo fato de programa_id ser null na tabela relacional
                ->where('user_id', Auth::id())
                ->whereNull('programa_id')
                ->whereIn('funcao', ['Serviço de Pós-Graduação', 'Coordenadores da Pós-Graduação'])
                ->exists())
            return self::query();

        return self::where('classe_nome', 'Seleções')
                 ->orWhere('classe_nome', 'Solicitações de Isenção de Taxa')
                 ->orWhereHas('niveisprogramas', function ($query) {
                    $query->whereIn('programa_id', Auth::user()->listarProgramasGerenciados()->pluck('id'));
                 });
    }

    /**
     * relacionamento com seleções
     */
    public function selecoes()
    {
        return $this->belongsToMany('App\Models\Selecao', 'selecao_tipoarquivo', 'tipoarquivo_id', 'selecao_id')->withTimestamps();
    }

    /**
     * relacionamento com combinações de níveis com programas
     */
    public function niveisprogramas()
    {
        return $this->belongsToMany('App\Models\NivelPrograma', 'tipoarquivo_nivelprograma', 'tipoarquivo_id', 'nivelprograma_id')->withTimestamps();
    }

    /**
     * relacionamento com arquivos
     */
    public function arquivos()
    {
        return $this->hasMany('App\Models\Arquivo', 'tipoarquivo_id');
    }

    /*
     * relacionamento com categorias
     */
    public function categorias()
    {
        return $this->belongsToMany('App\Models\Categoria', 'tipoarquivo_categoria', 'tipoarquivo_id', 'categoria_id')->withTimestamps();
    }
}
