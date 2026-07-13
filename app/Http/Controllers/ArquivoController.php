<?php

namespace App\Http\Controllers;

use App\Models\Arquivo;
use App\Models\Disciplina;
use App\Models\Inscricao;
use App\Models\LinhaPesquisa;
use App\Models\Matricula;
use App\Models\MotivoIsencaoTaxa;
use App\Models\Nivel;
use App\Models\NivelLinhaPesquisa;
use App\Models\Parametro;
use App\Models\Programa;
use App\Models\Selecao;
use App\Models\SolicitacaoIsencaoTaxa;
use App\Models\TipoArquivo;
use App\Models\User;
use App\Services\ZipService;
use App\Utils\ClasseUtils;
use App\Utils\JSONForms;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class ArquivoController extends Controller
{
    protected $zipService;

    public function __construct(ZipService $zipService)
    {
        $this->middleware('auth')->except('show');
        $this->zipService = $zipService;
    }

    public function index()
    {
        // pelo fato de eu ter definido as rotas do ArquivoController com Route::resource, o Laravel espera que exista esta action, mesmo que eu nunca a invoque
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Arquivo        $arquivo
     * @return \Illuminate\Http\Response
     */
    public function show(Arquivo $arquivo)
    {
        if ($arquivo->selecoes()->exists())
            $classe_nome = 'Selecao';
        elseif ($arquivo->solicitacoesisencaotaxa()->exists())
            $classe_nome = 'SolicitacaoIsencaoTaxa';
        elseif ($arquivo->inscricoes()->exists())
            $classe_nome = 'Inscricao';
        elseif ($arquivo->matriculas()->exists())
            $classe_nome = 'Matricula';
        Gate::authorize('arquivos.view', [$arquivo, $classe_nome]);

        while (ob_get_level() > 0)    // este while é para não estourar erro quando usando docker
            ob_end_clean();           // https://stackoverflow.com/questions/39329299/laravel-file-downloaded-from-storage-folder-gets-corrupted

        return Storage::download($arquivo->caminho, $arquivo->nome_original);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request   $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $classe_nome = fixJson($request->classe_nome);
        $classe_nome_plural = ClasseUtils::obterClasseNomePlural($classe_nome);
        $classe = ClasseUtils::obterClasse($classe_nome);
        $objeto = $classe::find($request->objeto_id);
        $classe_nome_plural_acentuado = ClasseUtils::obterClasseNomePluralAcentuado($classe_nome);
        $classe_nome_abreviada = ClasseUtils::obterClasseNomeAbreviada($classe_nome);
        $form = $this->obterForm($classe_nome, $objeto);
        $tipoarquivo = TipoArquivo::where('classe_nome', $classe_nome_plural_acentuado)->where('nome', $request->tipoarquivo)->first();

        $validator = \Validator::make($request->all(), [
            'arquivo.*' => 'required|mimes:pdf|max:' . config('selecoes-pos.upload_max_filesize'),
            'objeto_id' => 'required|integer|exists:' . $classe_nome_plural . ',id',
        ]);
        if ($validator->fails()) {
            \UspTheme::activeUrl($classe_nome_plural);
            return redirect()->to(url($classe_nome_plural . '/edit/' . $objeto->id))->with($this->monta_compact($objeto, $classe_nome, $classe_nome_plural, $form, 'edit', 'arquivos'))->withErrors($validator)->withInput();    // se fosse return view, cairia em URL arquivos e perderia da URL o segmento que diz se é solicitações de isenção de taxa, inscrições ou matrículas
        }
        Gate::authorize('arquivos.create', [$objeto, $classe_nome]);

        // transaction para não ter problema de inconsistência do DB
        $db_transaction = DB::transaction(function () use ($request, $classe_nome, $classe_nome_plural, $classe_nome_plural_acentuado, $classe_nome_abreviada, $objeto, $tipoarquivo) {

            foreach ($request->arquivo as $arq) {
                $arquivo = new Arquivo;
                $arquivo->user_id = \Auth::user()->id;
                $arquivo->nome_original = $classe_nome_abreviada . $objeto->id . '_'
                                            . $tipoarquivo->abreviacao . '_'
                                            . formatarDataHoraAtualComMilissegundos()
                                            . '.' . pathinfo($arq->getClientOriginalName(), PATHINFO_EXTENSION);
                $arquivo->caminho = $arq->store('./arquivos/' . $objeto->created_at->year);
                $arquivo->mimeType = $arq->getClientMimeType();
                $arquivo->tipoarquivo_id = $tipoarquivo->id;
                $arquivo->saveQuietly();    // vamos salvar sem evento pois a classe ainda não está cadastrada

                $arquivo->{$classe_nome_plural}()->attach($objeto->id, ['tipo' => $request->tipoarquivo]);
            }

            if ($classe_nome == 'Selecao') {
                $objeto->atualizarStatus();
                $objeto->estado = Selecao::where('id', $objeto->id)->value('estado');

                $request->session()->flash('alert-success', 'Documento(s) adicionado(s) com sucesso<br />');
            } else {
                $request->session()->flash('alert-success', 'Documento(s) adicionado(s) com sucesso<br />' .
                    'Se não houver mais arquivos a enviar, clique no botão "Enviar ' . ucfirst(explode(' ', ClasseUtils::obterClasseNomeFormatada($classe_nome))[0]) . '" abaixo para efetivá-la<br />' .
                    'Sem isso, ela não será avaliada!');
            }

            return ['objeto' => $objeto, 'arquivo' => $arquivo];    // basta retornar somente o último arquivo... desta forma, o evento created logo abaixo será disparado apenas uma vez
        });

        // agora sim vamos disparar o evento (necessário porque acima salvamos com saveQuietly)
        event('eloquent.created: App\Models\Arquivo', $db_transaction['arquivo']);

        \UspTheme::activeUrl($classe_nome_plural);
        return redirect()->to(url($classe_nome_plural . '/edit/' . $db_transaction['objeto']->id))->with($this->monta_compact($db_transaction['objeto'], $classe_nome, $classe_nome_plural, $form, 'edit', 'arquivos'));    // se fosse return view, um eventual F5 do usuário duplicaria o registro... POSTs devem ser com redirect
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request   $request
     * @param  \App\Models\Arquivo        $arquivo
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Arquivo $arquivo)
    {
        $classe_nome = fixJson($request->classe_nome);
        $classe_nome_plural = ClasseUtils::obterClasseNomePlural($classe_nome);
        $classe = ClasseUtils::obterClasse($classe_nome);
        $objeto = $classe::find($request->objeto_id);
        $form = $this->obterForm($classe_nome, $objeto);

        Gate::authorize('arquivos.delete', [$arquivo, $objeto, $classe_nome]);

        if (Storage::exists($arquivo->caminho))
            Storage::delete($arquivo->caminho);

        // transaction para não ter problema de inconsistência do DB
        $objeto = DB::transaction(function () use ($request, $arquivo, $classe_nome, $classe_nome_plural, $objeto) {

            $arquivo->{$classe_nome_plural}()->detach($objeto->id, ['tipo' => $request->tipoarquivo]);
            $arquivo->delete();

            if ($classe_nome == 'Selecao') {
                $objeto->atualizarStatus();
                $objeto->estado = Selecao::where('id', $objeto->id)->value('estado');
            }

            return $objeto;
        });

        $request->session()->flash('alert-success', 'Documento removido com sucesso');
        \UspTheme::activeUrl($classe_nome_plural);
        return redirect()->to(url($classe_nome_plural . '/edit/' . $objeto->id))->with($this->monta_compact($objeto, $classe_nome, $classe_nome_plural, $form, 'edit', 'arquivos'));
    }

    /**
     * Gera zip com todos os arquivos do objeto indicado
     *
     * @param  string  $classe_nome
     * @param  int     $objeto_id      - pelo fato do objeto poder ser de diferentes tipos, é melhor usarmos o id dele ao invés dele propriamente dito
     * @return \Illuminate\Http\JsonResponse
     */
    public function zipTodosDoObjeto(string $classe_nome, int $objeto_id)
    {
        $objeto = ClasseUtils::obterClasse($classe_nome)::findOrFail($objeto_id);
        Gate::authorize('arquivos.viewAny', [$objeto, $classe_nome]);

        $zip_name = ClasseUtils::obterClasseNomeAbreviada($classe_nome) . $objeto->id . '_' . formatarDataHoraAtualComMilissegundos() . '.zip';
        return $this->zip($objeto->arquivos, $zip_name);
    }

    /**
     * Faz o download do zip com todos os arquivos do objeto indicado
     *
     * @param  string  $classe_nome
     * @param  int     $objeto_id      - pelo fato do objeto poder ser de diferentes tipos, é melhor usarmos o id dele ao invés dele propriamente dito
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function downloadTodosDoObjeto(string $classe_nome, int $objeto_id, Request $request)
    {
        $objeto = ClasseUtils::obterClasse($classe_nome)::findOrFail($objeto_id);
        Gate::authorize('arquivos.viewAny', [$objeto, $classe_nome]);

        return $this->downloadZip($request->query('zip_name'));
    }

    /**
     * Gera zip com todos os arquivos de todos os objetos da classe indicada da seleção indicada
     *
     * @param  string               $classe_nome
     * @param  \App\Models\Selecao  $selecao
     * @return \Illuminate\Http\JsonResponse
     */
    public function zipTodosDosObjetosDaSelecao(string $classe_nome, Selecao $selecao)
    {
        Gate::authorize('selecoes.view', $selecao);

        $zip_name = ClasseUtils::obterClasseNomeAbreviada('Selecao') . $selecao->id . '_' . ClasseUtils::obterClasseNomeAbreviadaPlural($classe_nome) . '_' . formatarDataHoraAtualComMilissegundos() . '.zip';
        $arquivos = collect();
        switch ($classe_nome) {
            case 'SolicitacaoIsencaoTaxa':
                $arquivos = $selecao->solicitacoesisencaotaxa->flatMap(function ($solicitacaoisencaotaxa) { return $solicitacaoisencaotaxa->arquivos; });
                break;
            case 'Inscricao':
                $arquivos = $selecao->inscricoes->flatMap(function ($inscricao) { return $inscricao->arquivos; });
                break;
            case 'Matricula':
                $arquivos = $selecao->matriculas->flatMap(function ($matricula) { return $matricula->arquivos; });
        }
        return $this->zip($arquivos, $zip_name);
    }

    /**
     * Faz o download do zip com todos os arquivos de todos os objetos da classe indicada da seleção indicada
     *
     * @param  string               $classe_nome
     * @param  \App\Models\Selecao  $selecao
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function downloadTodosDosObjetosDaSelecao(string $classe_nome, Selecao $selecao, Request $request)
    {
        Gate::authorize('selecoes.view', $selecao);

        return $this->downloadZip($request->query('zip_name'));
    }

    private function zip(Collection $arquivos, string $zip_name)
    {
        $totalsize = $arquivos->sum(function ($arquivo) { return Storage::size($arquivo->caminho); });
        ini_set('max_execution_time', $this->obterTimeoutMaximo($totalsize));    // aumenta o tempo máximo de execução deste método com base no tamanho do arquivo a baixar

        $zip_fullfilename = $this->zipService->gerarZip($arquivos, $zip_name);
        if (!$zip_fullfilename)
            return response()->json(['status' => 'erro', 'mensagem' => 'Erro ao gerar o arquivo zip.']);

        return response()->json(['status' => 'concluído', 'zip_name' => $zip_name]);
    }

    private function downloadZip(string $zip_name)
    {
        $zip_fullfilename = storage_path('app/temp/' . $zip_name);
        if (!File::exists($zip_fullfilename))
            return response('Arquivo zip não encontrado.', 404);

        $totalsize = filesize($zip_fullfilename);
        ini_set('max_execution_time', $this->obterTimeoutMaximo($totalsize));    // aumenta o tempo máximo de execução deste método com base no tamanho do arquivo a baixar

        while (ob_get_level() > 0)    // este while é para não estourar erro quando usando docker
            ob_end_clean();           // sem este clean, o arquivo zip será baixado corrompido

        return response()->download($zip_fullfilename, basename($zip_fullfilename))->deleteFileAfterSend(true);
    }

    private function obterTimeoutMaximo($filesize)
    {
        $filesize = $filesize / (1024 * 1024 * 1024);    // tamanho do arquivo em Gb
        return max(60, ceil($filesize * env('selecoes-pos.timeout_por_gb')));    // o tempo máximo será de no mínimo 60 segundos
    }

    private function obterForm(string $classe_nome, object $objeto) {
        switch ($classe_nome) {
            case 'Selecao':
                return null;

            case 'SolicitacaoIsencaoTaxa':
                $objeto->selecao->template_solicitacoesisencaotaxa = JSONForms::orderTemplate($objeto->selecao->template_solicitacoesisencaotaxa);
                return JSONForms::generateForm($objeto->selecao, $classe_nome, $objeto);

            case 'Inscricao':
                $objeto->selecao->template_inscricoes = JSONForms::orderTemplate($objeto->selecao->template_inscricoes);
                return JSONForms::generateForm($objeto->selecao, $classe_nome, $objeto);

            case 'Matricula':
                $objeto->selecao->template_matriculas = JSONForms::orderTemplate($objeto->selecao->template_matriculas);
                return JSONForms::generateForm($objeto->selecao, $classe_nome, $objeto);
        }
    }

    private function monta_compact(object $objeto, string $classe_nome, string $classe_nome_plural, $form, string $modo, ?string $scroll = null)
    {
        $data = (object) ('App\\Http\\Controllers\\' . $classe_nome . 'Controller')::$data;
        $selecao = ($classe_nome == 'Selecao' ? $objeto : $objeto->selecao);
        $disciplinas = Disciplina::all();
        $motivosisencaotaxa = MotivoIsencaoTaxa::listarMotivosIsencaoTaxa();
        $responsaveis = $selecao->programa?->obterResponsaveis() ?? (new Programa())->obterResponsaveis();
        $extras = json_decode($objeto->extras, true);
        $objeto->niveislinhaspesquisa = NivelLinhaPesquisa::obterNiveisLinhasPesquisaDaSelecao($selecao);
        $niveislinhaspesquisa = NivelLinhaPesquisa::obterNiveisLinhasPesquisaPossiveis($selecao->programa_id);
        $objeto_disciplinas = ((isset($extras['disciplinas']) && is_array($extras['disciplinas'])) ? Disciplina::whereIn('id', $extras['disciplinas'])->get() : collect());
        $nivel = (isset($extras['nivel']) ? Nivel::where('id', $extras['nivel'])->first()->nome : '');
        $solicitacaoisencaotaxa_aprovada = (in_array($classe_nome, ['Inscricao', 'Matricula'])) ? SolicitacaoIsencaoTaxa::where('extras->cpf', $extras['cpf'] ?? null)
                                                                                                                        ->where('selecao_id', $objeto->selecao->id)
                                                                                                                        ->where('estado', 'LIKE', 'Isenção de Taxa Aprovada%')->first() : null;
        $niveis_selecao = ($selecao->categoria?->nome == 'Aluno Especial' ? new Collection() : (!empty($nivel) ? collect([['nome' => $nivel]]) : Nivel::all()));
        $objeto->tiposarquivo = TipoArquivo::obterTiposArquivoDaSelecao($classe_nome, $niveis_selecao, $selecao);
        $tiposarquivo_selecao = TipoArquivo::obterTiposArquivoPossiveis('Selecao', null, $selecao->programa_id);
        if ($classe_nome == 'Selecao') {
            $objeto->disciplinas = $objeto->disciplinas->sortBy('sigla');
            $objeto->tiposarquivo = TipoArquivo::obterTiposArquivoPossiveis('Selecao', null, $selecao->programa_id)
                                ->filter(function ($tipoarquivo) use ($selecao) { return ($tipoarquivo->nome !== 'Normas para Isenção de Taxa') || $selecao->tem_taxa; })
                            ->merge(TipoArquivo::obterTiposArquivoDaSelecao('SolicitacaoIsencaoTaxa', null, $selecao))
                            ->merge(TipoArquivo::obterTiposArquivoDaSelecao('Inscricao', $niveis_selecao, $selecao))
                            ->merge(TipoArquivo::obterTiposArquivoDaSelecao('Matricula', $niveis_selecao, $selecao)
                                ->filter(function ($tipoarquivo) { return !str_starts_with($tipoarquivo->nome, 'Boleto(s) de Pagamento'); }))
                                ->sortBy(function ($tipoarquivo) { return str_starts_with($tipoarquivo->nome, 'Boleto(s) de Pagamento') ? 1 : 0; });
        } elseif (in_array($classe_nome, ['Inscricao', 'Matricula'])) {
            $objeto->tiposarquivo = $objeto->tiposarquivo->filter(function ($tipoarquivo) use ($selecao) { return (!str_starts_with($tipoarquivo->nome, 'Boleto(s) de Pagamento')) || $selecao->tem_taxa; })
                                                         ->sortBy(function ($tipoarquivo) { return str_starts_with($tipoarquivo->nome, 'Boleto(s) de Pagamento') ? 1 : 0; });
            $tiposarquivo_selecao = $tiposarquivo_selecao->filter(function ($tipoarquivo) use ($selecao) { return ($tipoarquivo->nome !== 'Normas para Isenção de Taxa') || $selecao->tem_taxa; });
        }
        $tiposarquivo_solicitacaoisencaotaxa = TipoArquivo::obterTiposArquivoPossiveis('SolicitacaoIsencaoTaxa', null, $selecao->programa_id);
        $tiposarquivo_inscricao = TipoArquivo::obterTiposArquivoPossiveis('Inscricao', ($selecao->categoria->nome == 'Aluno Especial' ? new Collection() : Nivel::all()), $selecao->programa_id);
        $tiposarquivo_matricula = TipoArquivo::obterTiposArquivoPossiveis('Matricula', ($selecao->categoria->nome == 'Aluno Especial' ? new Collection() : Nivel::all()), $selecao->programa_id);
        $boleto_momento_envio = Parametro::first()->boleto_momento_envio;
        $max_upload_size = config('selecoes-pos.upload_max_filesize');

        return compact('data', 'objeto', 'classe_nome', 'classe_nome_plural', 'form', 'modo', 'disciplinas', 'motivosisencaotaxa', 'responsaveis', 'niveislinhaspesquisa', 'objeto_disciplinas', 'nivel', 'solicitacaoisencaotaxa_aprovada', 'tiposarquivo_selecao', 'tiposarquivo_solicitacaoisencaotaxa', 'tiposarquivo_inscricao', 'tiposarquivo_matricula', 'boleto_momento_envio', 'max_upload_size', 'scroll');
    }
}
