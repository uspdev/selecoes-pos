<?php

namespace App\Http\Controllers;

use App\Http\Requests\MatriculaRequest;
use App\Jobs\AtualizaStatusSelecoes;
use App\Mail\MatriculaMail;
use App\Models\Arquivo;
use App\Models\Disciplina;
use App\Models\LinhaPesquisa;
use App\Models\LocalUser;
use App\Models\Matricula;
use App\Models\Nivel;
use App\Models\Orientador;
use App\Models\Parametro;
use App\Models\Programa;
use App\Models\Selecao;
use App\Models\SolicitacaoIsencaoTaxa;
use App\Models\TipoArquivo;
use App\Models\User;
use App\Services\BoletoService;
use App\Utils\JSONForms;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class MatriculaController extends Controller
{
    protected $boletoService;

    // crud generico
    public static $data = [
        'title' => 'Matrículas',
        'url' => 'matriculas',     // caminho da rota do resource
        'modal' => true,
        'showId' => false,
        'viewBtn' => true,
        'editBtn' => false,
        'model' => 'App\Models\Matricula',
    ];

    public function __construct(BoletoService $boletoService)
    {
        $this->middleware('auth')->except([
            'listaSelecoesParaNovaMatricula',
            'create',
            'store'
        ]);    // exige que o usuário esteja logado, exceto para estes métodos listados
        $this->boletoService = $boletoService;
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (in_array(session('perfil'), ['admin', 'gerente', 'docente']))
            Gate::authorize('matriculas.viewAny');
        else
            Gate::authorize('matriculas.viewTheir');

        \UspTheme::activeUrl('matriculas');
        return view('matriculas.index', $this->monta_compact_index());
    }

    /**
     * Mostra lista de seleções e respectivas categorias
     * para selecionar e criar nova matrícula
     *
     * @param  \Illuminate\Http\Request   $request
     * @return \Illuminate\Http\Response
     */
    public function listaSelecoesParaNovaMatricula(Request $request)
    {
        Gate::authorize('matriculas.create');

        $request->validate(['filtro' => 'nullable|string']);

        \UspTheme::activeUrl('matriculas/create');
        AtualizaStatusSelecoes::dispatch()->onConnection('sync');
        $categorias = Selecao::listarSelecoesParaNovaMatricula();    // obtém as seleções dentro das categorias
        return view('matriculas.listaselecoesparanovamatricula', compact('categorias'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param  \App\Models\Selecao        $selecao
     * @param  ?\App\Models\Nivel         $nivel
     * @return \Illuminate\Http\Response
     */
    public function create(Selecao $selecao, ?Nivel $nivel = null)
    {
        Gate::authorize('matriculas.create', $selecao);

        $matricula = new Matricula;
        $matricula->selecao = $selecao;
        $user = Auth::user();
        // se o usuário já solicitou isenção de taxa para esta seleção...
        $solicitacaoisencaotaxa = $user->solicitacoesIsencaoTaxa()?->where('selecao_id', $selecao->id)->first();
        if ($solicitacaoisencaotaxa) {
            $solicitacaoisencaotaxa_extras = json_decode($solicitacaoisencaotaxa->extras, true);
            $extras = array(
                'nome' => $user->name,
                'tipo_de_documento' => $solicitacaoisencaotaxa_extras['tipo_de_documento'],
                'numero_do_documento' => $solicitacaoisencaotaxa_extras['numero_do_documento'],
                'cpf' => $solicitacaoisencaotaxa_extras['cpf'],
                'celular' => ((!Str::contains($user->telefone, 'ramal USP')) ? $user->telefone : ''),
                'e_mail' => $user->email,
            );
        } else
            $extras = array(
                'nome' => $user->name,
                'celular' => ((!Str::contains($user->telefone, 'ramal USP')) ? $user->telefone : ''),
                'e_mail' => $user->email,
            );
        if ($selecao->categoria->nome !== 'Aluno Especial')
            $extras['nivel'] = $nivel->id;
        $matricula->extras = json_encode($extras);

        \UspTheme::activeUrl('matriculas/create');
        return view('matriculas.edit', $this->monta_compact($matricula, 'create'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request        $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $selecao = Selecao::find($request->selecao_id);
        Gate::authorize('matriculas.create', $selecao);

        $user = \Auth::user();

        // transaction para não ter problema de inconsistência do DB
        $matricula = DB::transaction(function () use ($request, $user, $selecao) {

            // grava a matrícula
            $matricula = new Matricula;
            $matricula->selecao_id = $selecao->id;
            $matricula->estado = 'Aguardando Envio';
            $matricula->extras = json_encode($request->extras);
            $matricula->saveQuietly();      // vamos salvar sem evento pois o autor ainda não está cadastrado
            $matricula->load('selecao');    // com isso, $matricula->selecao é carregado
            $matricula->users()->attach($user, ['papel' => 'Autor']);

            return $matricula;
        });

        // agora sim vamos disparar o evento (necessário porque acima salvamos com saveQuietly)
        event('eloquent.created: App\Models\Matricula', $matricula);

        $matricula->agendarTarefa();

        $request->session()->flash('alert-success', 'Envie os documentos necessários para a avaliação da sua matrícula<br />' .
            'Sem eles, sua matrícula não será avaliada!');
        \UspTheme::activeUrl('matriculas/create');
        return redirect()->to(url('matriculas/edit/' . $matricula->id))->with($this->monta_compact($matricula, 'edit', 'arquivos'));    // se fosse return view, um eventual F5 do usuário duplicaria o registro... POSTs devem ser com redirect
    }

    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request   $request
     * @param  \App\Models\Matricula      $matricula
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, Matricula $matricula)
    {
        Gate::authorize('matriculas.view', $matricula);    // este 1o passo da edição é somente um show, não chega a haver um update

        \UspTheme::activeUrl('matriculas');
        $matricula->selecao->atualizarStatus();
        return view('matriculas.edit', $this->monta_compact($matricula, 'edit', session('scroll')));    // repassa scroll que eventualmente veio de redirect()->to(url(
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request   $request
     * @param  \App\Models\Matricula      $matricula
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Matricula $matricula)
    {
        if ($matricula->estado === 'Aprovada') {
            $request->session()->flash('alert-danger', 'Matrícula já aprovada não pode ser editada.');
            \UspTheme::activeUrl('matriculas');
            return view('matriculas.edit', $this->monta_compact($matricula, 'edit'));
        }

        if ($request->input('acao', null) == 'envio') {
            Gate::authorize('matriculas.update', $matricula);

            $extras = json_decode(stripslashes($matricula->extras), true);
            if ($matricula->todosArquivosRequeridosPresentes($extras['nivel'] ?? null)) {

                $disciplinas_id = (isset($extras['disciplinas']) ? $extras['disciplinas'] : []);
                if (($matricula->selecao->categoria->nome != 'Aluno Especial') || (count($disciplinas_id) > 0)) {

                    // verifica se ultrapassou o máximo de disciplinas para aluno especial
                    $cpf = $extras['cpf'];
                    $qtde_disciplinas_matriculas_anteriores = Matricula::where('extras->cpf', $cpf)->where('selecao_id', $matricula->selecao->id)->where('estado', 'Enviada')->sum(DB::raw('JSON_LENGTH(extras->"$.disciplinas")'));
                    if (($matricula->selecao->categoria->nome != 'Aluno Especial') ||
                        (count($disciplinas_id) + $qtde_disciplinas_matriculas_anteriores <= (Parametro::first()?->max_disciplinas_aluno_especial ?: PHP_INT_MAX))) {
                        $matricula->estado = 'Enviada';
                        $matricula->save();

                        $info_adicional = '';
                        $user = \Auth::user();
                        if ($matricula->selecao->tem_taxa && !SolicitacaoIsencaoTaxa::where('extras->cpf', $cpf ?? null)->where('selecao_id', $matricula->selecao->id)->whereIn('estado', ['Isenção de Taxa Aprovada', 'Isenção de Taxa Aprovada Após Recurso'])->exists())
                            if ((Parametro::first()->boleto_momento_envio == 'Envio da Inscrição/Matrícula') && $matricula->boletoFoiGerado)
                                $info_adicional = ($matricula->selecao->categoria->nome !== 'Aluno Especial' ? ' e seu boleto foi enviado, não deixe de pagá-lo' : ((count($disciplinas_id) == 1) ? ' e seu boleto foi enviado, não deixe de pagá-lo' : ' e seus boletos foram enviados, não deixe de pagá-los'));

                        $request->session()->flash('alert-success', 'Sua matrícula foi enviada' . $info_adicional);
                        \UspTheme::activeUrl('matriculas');
                        return redirect()->to(url('matriculas'))->with($this->monta_compact_index());    // se fosse return view, um eventual F5 do usuário duplicaria o registro... POSTs devem ser com redirect
                    } else {
                        $request->session()->flash('alert-danger', 'Você pode se matricular em no máximo ' . Parametro::first()->max_disciplinas_aluno_especial . ' disciplina(s) como aluno especial');
                        \UspTheme::activeUrl('matriculas');
                        return view('matriculas.edit', $this->monta_compact($matricula, 'edit', 'disciplinas'));
                    }
                } else {
                    $request->session()->flash('alert-danger', 'É necessário antes escolher a(s) disciplina(s)');
                    \UspTheme::activeUrl('matriculas');
                    return view('matriculas.edit', $this->monta_compact($matricula, 'edit', 'disciplinas'));
                }
            } else {
                $request->session()->flash('alert-danger', 'É necessário antes enviar todos os documentos exigidos');
                \UspTheme::activeUrl('matriculas');
                return view('matriculas.edit', $this->monta_compact($matricula, 'edit', 'arquivos'));
            }
        }

        if ($request->conjunto_alterado == 'estado') {
            Gate::authorize('matriculas.updateStatus', $matricula);

            $matricula->estado = $request->estado;
            $matricula->save();

            $request->session()->flash('alert-success', 'Estado da matrícula alterado com sucesso');

        } else {
            Gate::authorize('matriculas.update', $matricula);

            $extras = json_decode($matricula->extras, true);
            if (isset($extras['disciplinas']))
                $request->merge(['extras' => array_merge($request->input('extras', []), ['disciplinas' => $extras['disciplinas']])]);    // pelo fato de vir do card-principal, $request->extras não vem com as disciplinas... então precisamos recuperá-las a partir de $extras
            $matricula->extras = json_encode($request->input('extras'));
            $matricula->save();

            $request->session()->flash('alert-success', 'Matrícula alterada com sucesso');
        }

        \UspTheme::activeUrl('matriculas');
        return view('matriculas.edit', $this->monta_compact($matricula, 'edit'));
    }

    /**
     * Adiciona uma disciplina relacionada à matrícula
     * autorizado a qualquer um que tenha acesso à matrícula
     */
    public function storeDisciplina(Request $request, Matricula $matricula)
    {
        Gate::authorize('matriculas.update', $matricula);

        $request->validate([
            'id' => 'required',
        ],
        [
            'id.required' => 'Disciplina obrigatória',
        ]);

        // transaction para não ter problema de inconsistência do DB
        $db_transaction = DB::transaction(function () use ($request, $matricula) {

            $info_adicional = '';
            $disciplina = Disciplina::where('id', $request->id)->first();

            $extras = json_decode($matricula->extras, true);
            $disciplinas_id = (isset($extras['disciplinas']) ? $extras['disciplinas'] : []);
            $existia = is_array($disciplinas_id) && in_array($request->id, $disciplinas_id);

            if (!$existia) {
                $extras['disciplinas'][] = $request->id;
                $matricula->extras = json_encode($extras);

                if (Parametro::first()->boleto_momento_envio == 'Envio da Inscrição/Matrícula')
                    // se já havia enviado a matrícula, avisa para reenviá-la
                    if ($matricula->estado == 'Enviada') {
                        $matricula->estado = 'Aguardando Envio';
                        $info_adicional = '<br />Reenvie esta matrícula para gerar ' . ((count($extras['disciplinas']) == 1) ? 'novo boleto' : 'novos boletos');
                    }

                $matricula->save();
            }

            return ['disciplina' => $disciplina, 'existia' => $existia, 'info_adicional' => $info_adicional];
        });

        if (!$db_transaction['existia'])
            $request->session()->flash('alert-success', 'A disciplina ' . $db_transaction['disciplina']->sigla . ' - ' . $db_transaction['disciplina']->nome . ' foi adicionada à essa matrícula.' . $db_transaction['info_adicional']);
        else
            $request->session()->flash('alert-info', 'A disciplina ' . $db_transaction['disciplina']->sigla . ' - ' . $db_transaction['disciplina']->nome . ' já estava vinculada à essa matrícula.');
        \UspTheme::activeUrl('matriculas');
        return redirect()->to(url('matriculas/edit/' . $matricula->id))->with($this->monta_compact($matricula, 'edit', 'disciplinas'));    // se fosse return view, um eventual F5 do usuário duplicaria o registro... POSTs devem ser com redirect
    }

    /**
     * Remove uma disciplina relacionada à matrícula
     */
    public function destroyDisciplina(Request $request, Matricula $matricula, Disciplina $disciplina)
    {
        Gate::authorize('matriculas.update', $matricula);

        $info_adicional = '';

        $extras = json_decode($matricula->extras, true);
        $disciplinas_id = (isset($extras['disciplinas']) ? $extras['disciplinas'] : []);
        $indice = array_search($disciplina->id, $disciplinas_id);

        if ($indice !== false) {
            unset($extras['disciplinas'][$indice]);
            $matricula->extras = json_encode($extras);

            if (Parametro::first()->boleto_momento_envio == 'Envio da Inscrição/Matrícula')
                // se já havia enviado a matrícula, avisa para reenviá-la
                if ($matricula->estado == 'Enviada') {
                    $matricula->estado = 'Aguardando Envio';
                    $info_adicional = '<br />Reenvie esta matrícula para gerar ' . ((count($extras['disciplinas']) == 1) ? 'novo boleto' : 'novos boletos');
                }

            $matricula->save();
        }

        $request->session()->flash('alert-success', 'A disciplina ' . $disciplina->sigla . ' - '. $disciplina->nome . ' foi removida dessa matrícula.' . $info_adicional);
        \UspTheme::activeUrl('matriculas');
        return redirect()->to(url('matriculas/edit/' . $matricula->id))->with($this->monta_compact($matricula, 'edit', 'disciplinas'));    // se fosse return view, um eventual F5 do usuário duplicaria o registro... POSTs devem ser com redirect
    }

    /**
     * Gera o(s) boleto(s) para a matrícula - usado por admins para gerar manualmente o(s) boleto(s), caso necessário
     */
    public function geraBoletos(Request $request, Matricula $matricula)
    {
        if ($matricula->selecao->categoria->nome !== 'Aluno Especial') {
            // gera o boleto da matrícula
            if (empty($this->boletoService->gerarBoleto($matricula, 'Matricula')['nome_original'])) {
                $request->session()->flash('alert-danger', 'Não foi possível gerar o boleto para essa matrícula.');
                \UspTheme::activeUrl('matriculas');
                return redirect()->to(url('matriculas/edit/' . $matricula->id))->with($this->monta_compact($matricula, 'edit'));    // se fosse return view, um eventual F5 do usuário duplicaria o registro... POSTs devem ser com redirect
            }
        } else
            // gera um boleto para cada disciplina solicitada
            foreach ($request->disciplinas as $sigla => $valor)
                if (empty($this->boletoService->gerarBoleto($matricula, 'Matricula', $sigla)['nome_original'])) {
                    $request->session()->flash('alert-danger', 'Não foi possível gerar o boleto da disciplina ' . $sigla . ' para essa matrícula<br />' .
                        'A geração do(s) boleto(s) foi abortada');
                    \UspTheme::activeUrl('matriculas');
                    return redirect()->to(url('matriculas/edit/' . $matricula->id))->with($this->monta_compact($matricula, 'edit'));
                }

        $request->session()->flash('alert-success', ($matricula->selecao->categoria->nome !== 'Aluno Especial' ? 'O boleto foi gerado com sucesso' : 'O(s) boleto(s) foi(ram) gerado(s) com sucesso'));
        \UspTheme::activeUrl('matriculas');
        return redirect()->to(url('matriculas/edit/' . $matricula->id))->with($this->monta_compact($matricula, 'edit', 'arquivos'));
    }

    /**
     * Envia um boleto da matrícula
     */
    public function enviaBoleto(Request $request, Matricula $matricula, Arquivo $arquivo)
    {
        if (!$arquivo || !$arquivo->matriculas->contains($matricula)) {
            $request->session()->flash('alert-danger', 'Esse documento não existe ou não pertence a essa matrícula');
            \UspTheme::activeUrl('matriculas');
            return redirect()->to(url('matriculas/edit/' . $matricula->id))->with($this->monta_compact($matricula, 'edit'));
        }

        // envia e-mail para o candidato com o boleto
        // envio do e-mail "15" do README.md
        $passo = 'boleto - envio manual';
        $user = $matricula->pessoas('Autor');
        $arquivo->conteudo = base64_encode(Storage::get($arquivo->caminho));
        \Mail::to($user->email)
            ->queue(new MatriculaMail(compact('passo', 'matricula', 'user', 'arquivo')));

        $request->session()->flash('alert-success', 'O boleto foi enviado com sucesso');
        \UspTheme::activeUrl('matriculas');
        return redirect()->to(url('matriculas/edit/' . $matricula->id))->with($this->monta_compact($matricula, 'edit', 'arquivos'));
    }

    public function monta_compact_index()
    {
        $data = self::$data;
        $objetos = Matricula::listarMatriculas();
        foreach ($objetos as $objeto) {
            $extras = json_decode($objeto->extras, true);
            $objeto->linha_pesquisa = (isset($extras['linha_pesquisa']) ? (LinhaPesquisa::where('id', $extras['linha_pesquisa'])->first()->nome ?? null) : null);
            $objeto->disciplinas = (isset($extras['disciplinas']) ? (Disciplina::whereIn('id', $extras['disciplinas'])->orderBy('sigla')->get()->map(function ($disciplina) {
                return $disciplina->sigla . ' - ' . $disciplina->nome;
            })->implode(',<br />')) : null);
        }
        $classe_nome = 'Matricula';
        $max_upload_size = config('selecoes-pos.upload_max_filesize');
        $niveis = Nivel::all();

        return compact('data', 'objetos', 'classe_nome', 'max_upload_size', 'niveis');
    }

    public function monta_compact(Matricula $matricula, string $modo, ?string $scroll = null)
    {
        $data = (object) self::$data;
        $matricula->selecao->template_matriculas = JSONForms::orderTemplate($matricula->selecao->template_matriculas);
        $objeto = $matricula;
        $classe_nome = 'Matricula';
        $classe_nome_plural = 'matriculas';
        $form = JSONForms::generateForm($objeto->selecao, $classe_nome, $objeto);
        $responsaveis = $objeto->selecao->programa?->obterResponsaveis() ?? (new Programa())->obterResponsaveis();
        $extras = json_decode($objeto->extras, true);
        $objeto_disciplinas = ((isset($extras['disciplinas']) && is_array($extras['disciplinas'])) ? Disciplina::whereIn('id', $extras['disciplinas'])->orderBy('sigla')->get() : collect());
        $disciplinas = Disciplina::obterDisciplinasPossiveis($objeto->selecao);
        $nivel = (isset($extras['nivel']) ? Nivel::where('id', $extras['nivel'])->first()->nome : '');
        $objeto->tiposarquivo = TipoArquivo::obterTiposArquivoDaSelecao('Matricula', ($objeto->selecao->categoria?->nome == 'Aluno Especial' ? new Collection() : collect([['nome' => $nivel]])), $objeto->selecao)
            ->filter(function ($tipoarquivo) use ($matricula) { return (!str_starts_with($tipoarquivo->nome, 'Boleto(s) de Pagamento')) || $matricula->selecao->tem_taxa; })
            ->sortBy(function ($tipoarquivo) { return str_starts_with($tipoarquivo->nome, 'Boleto(s) de Pagamento') ? 1 : 0; });
        $tiposarquivo_selecao = TipoArquivo::obterTiposArquivoPossiveis('Selecao', null, $objeto->selecao->programa_id)
            ->filter(function ($tipoarquivo) use ($matricula) { return ($tipoarquivo->nome !== 'Normas para Isenção de Taxa') || $matricula->selecao->tem_taxa; });
        $solicitacaoisencaotaxa_aprovada = SolicitacaoIsencaoTaxa::where('extras->cpf', $extras['cpf'] ?? null)
                                                                 ->where('selecao_id', $objeto->selecao->id)
                                                                 ->where('estado', 'LIKE', 'Isenção de Taxa Aprovada%')->first();
        $disciplinas_sem_boleto = [];
        if ($matricula->selecao->categoria->nome == 'Aluno Especial')
            foreach ($objeto_disciplinas as $disciplina)
                if ($matricula->arquivos->filter(fn($a) => ($a->pivot->tipo == 'Boleto(s) de Pagamento') && str_contains(strtolower($a->nome_original), strtolower($disciplina->sigla)))->count() == 0)
                    $disciplinas_sem_boleto[] = $disciplina;
        $matricula->disciplinas_sem_boleto = $disciplinas_sem_boleto;
        $boleto_momento_envio = Parametro::first()->boleto_momento_envio;
        $max_upload_size = config('selecoes-pos.upload_max_filesize');

        return compact('data', 'objeto', 'classe_nome', 'classe_nome_plural', 'form', 'modo', 'responsaveis', 'objeto_disciplinas', 'disciplinas', 'nivel', 'tiposarquivo_selecao', 'solicitacaoisencaotaxa_aprovada', 'boleto_momento_envio', 'max_upload_size', 'scroll');
    }
}
