<?php

namespace App\Models;

use App\Jobs\AlertaCandidatoIncompletude;
use App\Observers\MatriculaObserver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class Matricula extends Model
{
    use HasFactory;

    # matrículas não segue convenção do laravel para nomes de tabela
    protected $table = 'matriculas';

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
        [
            'name' => 'linhapesquisa_id',
            'label' => 'Linha de Pesquisa/Tema',
            'type' => 'hidden',
            'model' => 'LinhaPesquisa',
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
        Matricula::observe(MatriculaObserver::class);
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
            'Aguardando Envio', 'Enviada',                          // decorrem de ações do candidato
            'Em Pré-Avaliação', 'Pré-Aprovada', 'Pré-Rejeitada',    // decorrem de ações dos(as) secretários(as) do programa da seleção da matrícula
            'Em Avaliação', 'Aprovada', 'Rejeitada'                 // decorrem de ações dos(as) secretários(as) do programa da seleção da matrícula
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
        // este método é invocado na criação de uma matrícula

        if ($this->selecao->fluxo_continuo) {
            // agenda job de alerta de matrícula não concluída
            $job_datahora = now()->addDays(7);
            if ($job_datahora < Carbon::parse($this->selecao->matriculas_datahora_fim)->subHours(24))
                AlertaCandidatoIncompletude::dispatch($this->id, 'Matricula')->delay($job_datahora);
        }
    }

    /**
     * Retorna a contagem de matrículas por ano
     *
     * Se passar $selecao a contagem é somente da seleção, se não é de todo o sistema
     *
     * @param  \App\Models\Selecao $selecao
     * @return int
     */
    public static function contarMatriculasPorAno(?Selecao $selecao = null)
    {
        return self::selectRaw('year(created_at) ano, count(*) count')
            ->where('selecao_id', $selecao->id)
            ->whereYear('created_at', '>=', date('Y') - 5) // ultimos 5 anos
            ->groupBy('ano')->get();
    }

    /**
     * Retorna a contagem de matrículas por mês de determinado ano
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
    public static function contarMatriculasPorMes(int $ano, ?Selecao $selecao = null)
    {
        $contagem = self::selectRaw('month(created_at) mes, count(*) count')
            ->where('selecao_id', $selecao->id)
            ->whereYear('created_at', $ano)
            ->groupBy('mes')->get();

        // vamos organizar em array por mês para facilitar a apresentação
        $ret = [];
        for ($i = 0; $i < 12; $i++)
            $ret[] = $contagem->where('mes', $i + 1)->first()->count ?? '';
        return $ret;
    }

    /**
     * Lista as matrículas autorizadas para o usuário
     *
     * Se perfiladmin mostra todas as matrículas
     * Se perfilusuario mostra as matrículas que ele está cadastrado como criador
     *
     * @return Collection
     */
    public static function listarMatriculas()
    {
        switch (session('perfil')) {
            case 'admin':
                $matriculas = self::with('selecao')->get();
                break;

            case 'gerente':
                if (DB::table('user_programa')    // não dá pra partir de $this->, pelo fato de programa_id ser null na tabela relacional
                        ->where('user_id', Auth::id())
                        ->whereNull('programa_id')
                        ->whereIn('funcao', ['Serviço de Pós-Graduação', 'Coordenadores(as) da Pós-Graduação'])
                        ->exists())
                    $matriculas = self::with('selecao')->get();
                else
                    $matriculas = self::with('selecao')->whereHas('selecao', function ($query) {
                        $query->whereIn('programa_id', Auth::user()->listarProgramasGerenciados()->pluck('id'));
                    })->get();
                break;

            case 'docente':
                $matriculas = self::with('selecao')->whereHas('selecao', function ($query) {
                    $query->whereIn('programa_id', Auth::user()->listarProgramasGerenciadosFuncao('Docentes do Programa')->pluck('id'));
                })->get();
                break;

            default:
                $matriculas = Auth::user()->matriculas()->with('selecao')->wherePivotIn('papel', ['Autor'])->get();
        }

        $matriculas = $matriculas->filter(fn($matricula) => $matricula->selecao->fazMatriculas());

        $ultimasSelecoesIds = Selecao::obterUltimasSelecoesIds('Matricula');
        $matriculas->each(function ($matricula) use ($ultimasSelecoesIds) {
            $matricula->is_latest_selecoes = in_array($matricula->selecao_id, $ultimasSelecoesIds);
        });

        return $matriculas;
    }

    public static function listarMatriculasPorSelecao(Selecao $selecao, int $ano)
    {
        return self::where('selecao_id', $selecao->id)->whereYear('created_at', $ano)->get();
    }

    /**
     * Verifica se todos os arquivos requeridos da matrícula estão presentes
     * Conforme for o caso, altera o estado da matrícula
     */
    public function todosArquivosRequeridosPresentes(?int $nivel_id = null)
    {
        // obtém os tipos de arquivo requeridos
        $tiposarquivo_requeridos = TipoArquivo::obterTiposArquivoObrigatorios($this, 'Matrículas');
        if (!is_null($nivel_id))
            $tiposarquivo_requeridos = $tiposarquivo_requeridos->filter(function ($tipoarquivo) use ($nivel_id) {
                return $tipoarquivo->niveisprogramas()->where('nivel_id', $nivel_id)->when($this->selecao->exigePrograma(), function ($query) {
                    $query->where('programa_id', $this->selecao->programa_id);
                })->exists();
            });

        // obtém os tipos de arquivo da matrícula
        $arquivos_matricula = $this->arquivos->pluck('pivot.tipo')->countBy()->all();

        // verifica se todos os tipos requeridos estão presentes nos arquivos da matrícula
        $todos_requeridos_presentes = function() use ($tiposarquivo_requeridos, $arquivos_matricula) {
            foreach ($tiposarquivo_requeridos as $tipoarquivo_requerido) {
                $tipo_nome = $tipoarquivo_requerido['nome'];
                $minimo_requerido = ($tipoarquivo_requerido['minimum_required'] ?? 1);
                if (!isset($arquivos_matricula[$tipo_nome]) || ($arquivos_matricula[$tipo_nome] < $minimo_requerido))
                    return false;
            }
            return true;
        };
        return $todos_requeridos_presentes();
    }

    public bool $boletoFoiGerado = false;    // não persistido em banco, vive apenas enquanto durar esta instância do objeto em memória (tipicamente: MatriculaController, save(), MatriculaOserver, BoletoService, e volta para MatriculaController, e então a instância do objeto é destruída)

    /**
     * Mostra as pessoas que têm vínculo com a matrícula
     *
     * Se informado $pivot, retorna somente o primeiro usuário, senão retorna a lista completa
     *
     * @param  $pivot Papel da pessoa na matrícula (autor, null = todos)
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
        return $this->belongsToMany('App\Models\Arquivo', 'arquivo_matricula')->withPivot('tipo', 'disciplina')->withTimestamps();
    }

    /**
     * relacionamento com users
     */
    public function users()
    {
        return $this->belongsToMany('App\Models\User', 'user_matricula')->withTimestamps();
    }

    /**
     * relacionamento com seleção
     */
    public function selecao()
    {
        return $this->belongsTo(Selecao::class);
    }
}
