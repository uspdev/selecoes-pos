<?php

namespace App\Models;

use App\Jobs\AlertaCandidatoIncompletude;
use App\Observers\SolicitacaoIsencaoTaxaObserver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SolicitacaoIsencaoTaxa extends Model
{
    use HasFactory;

    # solicitações de isenção de taxa não segue convenção do laravel para nomes de tabela
    protected $table = 'solicitacoesisencaotaxa';

    protected $fillable = [
        'selecao_id',
    ];

    // uso no crud generico
    protected const fields = [
        [
            'name' => 'selecao_id',
            'label' => 'Seleção',
            'type' => 'hidden',
            'model' => 'Selecao',
            'data' => [],
        ],
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        SolicitacaoIsencaoTaxa::observe(SolicitacaoIsencaoTaxaObserver::class);
    }

    // uso no crud generico
    public static function getFields()
    {
        $fields = self::fields;
        foreach ($fields as &$field)
            if (substr($field['name'], -3) == '_id') {
                $class = '\\App\\Models\\' . $field['model'];
                $field['data'] = $class::allToSelect();
            }
        return $fields;
    }

    /**
     * lista de estados padrão
     */
    public static function estados()
    {
        return [
            'Aguardando Envio', 'Isenção de Taxa Solicitada',                                                                                   // decorrem de ações do candidato
            'Isenção de Taxa em Avaliação', 'Isenção de Taxa Aprovada', 'Isenção de Taxa Rejeitada', 'Isenção de Taxa Aprovada Após Recurso'    // decorrem de ações do serviço de pós-graduação
        ];
    }

    /**
     * Valores possiveis para pivot do relacionamento com users
     */
    #
    public static function pessoaPapeis($formSelect = false)
    {
        if ($formSelect)
            return ['Autor' => 'Autor'];
        else
            return ['Autor'];
    }

    public function agendarTarefa()
    {
        // este método é invocado na criação de uma solicitação de isenção de taxa

        if ($this->selecao->fluxo_continuo) {
            // agenda job de alerta de solicitação de isenção de taxa não concluída
            $job_datahora = now()->addDays(7);
            if ($job_datahora < Carbon::parse($this->selecao->solicitacoesisencaotaxa_datahora_fim)->subHours(24))
                AlertaCandidatoIncompletude::dispatch($this->id, 'SolicitacaoIsencaoTaxa')->delay($job_datahora);
        }
    }

    /**
     * Retorna a contagem de solicitações de isenção de taxa por ano
     *
     * Se passar $selecao a contagem é somente da seleção, se não é de todo o sistema
     *
     * @param  \App\Models\Selecao $selecao
     * @return int
     */
    public static function contarSolicitacoesIsencaoTaxaPorAno(?Selecao $selecao = null)
    {
        return self::selectRaw('year(created_at) ano, count(*) count')
            ->where('selecao_id', $selecao->id)
            ->whereYear('created_at', '>=', date('Y') - 5) // ultimos 5 anos
            ->groupBy('ano')->get();
    }

    /**
     * Retorna a contagem de solicitações de isenção de taxa por mês de determinado ano
     *
     * Se passar $selecao a contagem é somente da seleção, se não é de todo o sistema
     *
     * Retorno em array sendo o 1o elemento correspondente à contagem de janeiro,
     * o segundo elemento é a contagem de fevereiro, e assim por diante.
     * o array de retorno, portanto, possui 12 elementos
     *
     * @param  int $ano
     * @param  \App\Models\Selecao $selecao
     * @return array
     */
    public static function contarSolicitacoesIsencaoTaxaPorMes(int $ano, ?Selecao $selecao = null)
    {
        $contagem = self::selectRaw('month(created_at) mes, count(*) count')
            ->where('selecao_id', $selecao->id)
            ->whereYear('created_at', $ano)
            ->groupBy('mes')->get();

        // vamos organizar em array por mês para facilitar a apresentação
        $ret = [];
        for ($i = 0; $i < 12; $i++) {
            $ret[] = $contagem->where('mes', $i + 1)->first()->count ?? '';
        }
        return $ret;
    }

    /**
     * Lista as solicitações de isenção de taxa autorizadas para o usuário
     *
     * Se perfiladmin mostra todas as solicitações de isenção de taxa
     * Se perfilusuario mostra as solicitações de isenção de taxa que ele está cadastrado como criador
     *
     * @return Collection
     */
    public static function listarSolicitacoesIsencaoTaxa()
    {
        switch (session('perfil')) {
            case 'admin':
                $solicitacoesisencaotaxa = self::all();
                break;

            case 'gerente':
                if (DB::table('user_programa')    // não dá pra partir de $this->, pelo fato de programa_id ser null na tabela relacional
                        ->where('user_id', Auth::id())
                        ->whereNull('programa_id')
                        ->whereIn('funcao', ['Serviço de Pós-Graduação', 'Coordenadores da Pós-Graduação'])
                        ->exists())
                    $solicitacoesisencaotaxa = self::all();
                else
                    $solicitacoesisencaotaxa = self::with('selecao')->whereHas('selecao', function ($query) {
                        $query->whereIn('programa_id', Auth::user()->listarProgramasGerenciados()->pluck('id'));
                    })->get();
                break;

            case 'docente':
                $solicitacoesisencaotaxa = self::with('selecao')->whereHas('selecao', function ($query) {
                    $query->whereIn('programa_id', Auth::user()->listarProgramasGerenciadosFuncao('Docentes do Programa')->pluck('id'));
                })->get();
                break;

            default:
                $solicitacoesisencaotaxa = Auth::user()->solicitacoesisencaotaxa()->wherePivotIn('papel', ['Autor'])->get();
        }

        $ultimasSelecoesIds = Selecao::obterUltimasSelecoesIds('SolicitacaoIsencaoTaxa');
        $solicitacoesisencaotaxa->each(function ($solicitacaoisencaotaxa) use ($ultimasSelecoesIds) {
            $solicitacaoisencaotaxa->is_latest_selecoes = in_array($solicitacaoisencaotaxa->selecao_id, $ultimasSelecoesIds);
        });

        return $solicitacoesisencaotaxa;
    }

    public static function listarSolicitacoesIsencaoTaxaPorSelecao(Selecao $selecao, int $ano)
    {
        return self::where('selecao_id', $selecao->id)->whereYear('created_at', $ano)->get();
    }

    /**
     * Verifica se todos os arquivos requeridos da solicitação de isenção de taxa estão presentes
     */
    public function todosArquivosRequeridosPresentes()
    {
        // obtém os tipos de arquivo requeridos
        $tiposarquivo_requeridos = TipoArquivo::obterTiposArquivoObrigatorios($this, 'Solicitações de Isenção de Taxa');

        // obtém os tipos de arquivo da solicitação de isenção de taxa
        $arquivos_solicitacaoisencaotaxa = $this->arquivos->pluck('pivot.tipo')->countBy()->all();

        $todos_requeridos_presentes = function() use ($tiposarquivo_requeridos, $arquivos_solicitacaoisencaotaxa) {
            foreach ($tiposarquivo_requeridos as $tipoarquivo_requerido) {
                $tipo_nome = $tipoarquivo_requerido['nome'];
                $minimo_requerido = ($tipoarquivo_requerido['minimum_required'] ?? 1);
                if (!isset($arquivos_solicitacaoisencaotaxa[$tipo_nome]) || ($arquivos_solicitacaoisencaotaxa[$tipo_nome] < $minimo_requerido))
                    return false;
            }
            return true;
        };
        return $todos_requeridos_presentes();
    }

    /**
     * Mostra as pessoas que têm vínculo com a solicitação de isenção de taxa
     *
     * Se informado $pivot, retorna somente o primeiro usuário, senão retorna a lista completa
     *
     * @param  $pivot Papel da pessoa na solicitação de isenção de taxa (autor, null = todos)
     * @return App\Models\User|Collection
     */
    public function pessoas($pivot = null)
    {
        if ($pivot)
            return $this->users()->wherePivot('papel', $pivot)->first();
        else
            return $this->users()->withPivot('papel');
    }

    /**
     * relacionamento com arquivos
     */
    public function arquivos()
    {
        return $this->belongsToMany('App\Models\Arquivo', 'arquivo_solicitacaoisencaotaxa', 'solicitacaoisencaotaxa_id', 'arquivo_id')->withPivot('tipo')->withTimestamps();    // se eu não especificar o nome do campo como solicitacaoisencaotaxa_id, o Laravel vai pensar que é solicitacao_isencao_taxa_id, e vai dar erro
    }

    /**
     * relacionamento com users
     */
    public function users()
    {
        return $this->belongsToMany('App\Models\User', 'user_solicitacaoisencaotaxa', 'solicitacaoisencaotaxa_id', 'user_id')->withTimestamps();    // se eu não especificar o nome do campo como solicitacaoisencaotaxa_id, o Laravel vai pensar que é solicitacao_isencao_taxa_id, e vai dar erro
    }

    /**
     * relacionamento com seleção
     */
    public function selecao()
    {
        return $this->belongsTo(Selecao::class);
    }
}
