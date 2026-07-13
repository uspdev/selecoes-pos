<?php

namespace App\Http\Controllers;

use App\Http\Requests\InscricaoRequest;
use App\Jobs\AtualizaStatusSelecoes;
use App\Mail\InscricaoMail;
use App\Models\Arquivo;
use App\Models\Disciplina;
use App\Models\Inscricao;
use App\Models\LinhaPesquisa;
use App\Models\LocalUser;
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

class InscricaoController extends Controller
{
    protected $boletoService;

    // crud generico
    public static $data = [
        'title' => 'Inscrições',
        'url' => 'inscricoes',     // caminho da rota do resource
        'modal' => true,
        'showId' => false,
        'viewBtn' => true,
        'editBtn' => false,
        'model' => 'App\Models\Inscricao',
    ];

    public function __construct(BoletoService $boletoService)
    {
        $this->middleware('auth')->except([
            'listaSelecoesParaNovaInscricao',
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
            Gate::authorize('inscricoes.viewAny');
        else
            Gate::authorize('inscricoes.viewTheir');

        \UspTheme::activeUrl('inscricoes');
        return view('inscricoes.index', $this->monta_compact_index());
    }

    /**
     * Mostra lista de seleções e respectivas categorias
     * para selecionar e criar nova inscrição
     *
     * @param  \Illuminate\Http\Request   $request
     * @return \Illuminate\Http\Response
     */
    public function listaSelecoesParaNovaInscricao(Request $request)
    {
        Gate::authorize('inscricoes.create');

        $request->validate(['filtro' => 'nullable|string']);

        \UspTheme::activeUrl('inscricoes/create');
        AtualizaStatusSelecoes::dispatch()->onConnection('sync');
        $categorias = Selecao::listarSelecoesParaNovaInscricao();    // obtém as seleções dentro das categorias
        return view('inscricoes.listaselecoesparanovainscricao', compact('categorias'));
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
        Gate::authorize('inscricoes.create', $selecao);

        $inscricao = new Inscricao;
        $inscricao->selecao = $selecao;
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
        $inscricao->extras = json_encode($extras);

        \UspTheme::activeUrl('inscricoes/create');
        return view('inscricoes.edit', $this->monta_compact($inscricao, 'create'));
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
        Gate::authorize('inscricoes.create', $selecao);

        $user = \Auth::user();

        // transaction para não ter problema de inconsistência do DB
        $inscricao = DB::transaction(function () use ($request, $user, $selecao) {

            // grava a inscrição
            $inscricao = new Inscricao;
            $inscricao->selecao_id = $selecao->id;
            $inscricao->estado = 'Aguardando Envio';
            $inscricao->extras = json_encode($request->extras);
            $inscricao->saveQuietly();      // vamos salvar sem evento pois o autor ainda não está cadastrado
            $inscricao->load('selecao');    // com isso, $inscricao->selecao é carregado
            $inscricao->users()->attach($user, ['papel' => 'Autor']);

            return $inscricao;
        });

        // agora sim vamos disparar o evento (necessário porque acima salvamos com saveQuietly)
        event('eloquent.created: App\Models\Inscricao', $inscricao);

        $inscricao->agendarTarefa();

        $request->session()->flash('alert-success', 'Envie os documentos necessários para a avaliação da sua inscrição<br />' .
            'Sem eles, sua inscrição não será avaliada!');
        \UspTheme::activeUrl('inscricoes/create');
        return redirect()->to(url('inscricoes/edit/' . $inscricao->id))->with($this->monta_compact($inscricao, 'edit', 'arquivos'));    // se fosse return view, um eventual F5 do usuário duplicaria o registro... POSTs devem ser com redirect
    }

    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request   $request
     * @param  \App\Models\Inscricao      $inscricao
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, Inscricao $inscricao)
    {
        Gate::authorize('inscricoes.view', $inscricao);    // este 1o passo da edição é somente um show, não chega a haver um update

        \UspTheme::activeUrl('inscricoes');
        $inscricao->selecao->atualizarStatus();
        return view('inscricoes.edit', $this->monta_compact($inscricao, 'edit', session('scroll')));    // repassa scroll que eventualmente veio de redirect()->to(url(
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request   $request
     * @param  \App\Models\Inscricao      $inscricao
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Inscricao $inscricao)
    {
        if ($inscricao->estado === 'Aprovada') {
            $request->session()->flash('alert-danger', 'Inscrição já aprovada não pode ser editada.');
            \UspTheme::activeUrl('inscricoes');
            return view('inscricoes.edit', $this->monta_compact($inscricao, 'edit'));
        }

        if ($request->input('acao', null) == 'envio') {
            Gate::authorize('inscricoes.update', $inscricao);

            $extras = json_decode(stripslashes($inscricao->extras), true);
            if ($inscricao->todosArquivosRequeridosPresentes($extras['nivel'] ?? null)) {

                $disciplinas_id = (isset($extras['disciplinas']) ? $extras['disciplinas'] : []);
                if (($inscricao->selecao->categoria->nome != 'Aluno Especial') || (count($disciplinas_id) > 0)) {

                    // verifica se ultrapassou o máximo de disciplinas para aluno especial
                    $cpf = $extras['cpf'];
                    $qtde_disciplinas_inscricoes_anteriores = Inscricao::where('extras->cpf', $cpf)->where('selecao_id', $inscricao->selecao->id)->where('estado', 'Enviada')->sum(DB::raw('JSON_LENGTH(extras->"$.disciplinas")'));
                    if (($inscricao->selecao->categoria->nome != 'Aluno Especial') ||
                        (count($disciplinas_id) + $qtde_disciplinas_inscricoes_anteriores <= (Parametro::first()?->max_disciplinas_aluno_especial ?: PHP_INT_MAX))) {
                        $inscricao->estado = 'Enviada';
                        $inscricao->save();

                        $info_adicional = '';
                        $user = \Auth::user();
                        if ($inscricao->selecao->tem_taxa && !SolicitacaoIsencaoTaxa::where('extras->cpf', $cpf ?? null)->where('selecao_id', $inscricao->selecao->id)->whereIn('estado', ['Isenção de Taxa Aprovada', 'Isenção de Taxa Aprovada Após Recurso'])->exists())
                            if ((Parametro::first()->boleto_momento_envio == 'Envio da Inscrição/Matrícula') && $inscricao->boletoFoiGerado)
                                $info_adicional = ($inscricao->selecao->categoria->nome !== 'Aluno Especial' ? ' e seu boleto foi enviado, não deixe de pagá-lo' : ((count($disciplinas_id) == 1) ? ' e seu boleto foi enviado, não deixe de pagá-lo' : ' e seus boletos foram enviados, não deixe de pagá-los'));

                        $request->session()->flash('alert-success', 'Sua inscrição foi enviada' . $info_adicional);
                        \UspTheme::activeUrl('inscricoes');
                        return redirect()->to(url('inscricoes'))->with($this->monta_compact_index());    // se fosse return view, um eventual F5 do usuário duplicaria o registro... POSTs devem ser com redirect
                    } else {
                        $request->session()->flash('alert-danger', 'Você pode se inscrever em no máximo ' . Parametro::first()->max_disciplinas_aluno_especial . ' disciplina(s) como aluno especial');
                        \UspTheme::activeUrl('inscricoes');
                        return view('inscricoes.edit', $this->monta_compact($inscricao, 'edit', 'disciplinas'));
                    }
                } else {
                    $request->session()->flash('alert-danger', 'É necessário antes escolher a(s) disciplina(s)');
                    \UspTheme::activeUrl('inscricoes');
                    return view('inscricoes.edit', $this->monta_compact($inscricao, 'edit', 'disciplinas'));
                }
            } else {
                $request->session()->flash('alert-danger', 'É necessário antes enviar todos os documentos exigidos');
                \UspTheme::activeUrl('inscricoes');
                return view('inscricoes.edit', $this->monta_compact($inscricao, 'edit', 'arquivos'));
            }
        }

        if ($request->conjunto_alterado == 'estado') {
            Gate::authorize('inscricoes.updateStatus', $inscricao);

            $inscricao->estado = $request->estado;
            $inscricao->save();

            $request->session()->flash('alert-success', 'Estado da inscrição alterado com sucesso');

        } else {
            Gate::authorize('inscricoes.update', $inscricao);

            $extras = json_decode($inscricao->extras, true);
            if (isset($extras['disciplinas']))
                $request->merge(['extras' => array_merge($request->input('extras', []), ['disciplinas' => $extras['disciplinas']])]);    // pelo fato de vir do card-principal, $request->extras não vem com as disciplinas... então precisamos recuperá-las a partir de $extras
            $inscricao->extras = json_encode($request->input('extras'));
            $inscricao->save();

            $request->session()->flash('alert-success', 'Inscrição alterada com sucesso');
        }

        \UspTheme::activeUrl('inscricoes');
        return view('inscricoes.edit', $this->monta_compact($inscricao, 'edit'));
    }

    /**
     * Adiciona uma disciplina relacionada à inscrição
     * autorizado a qualquer um que tenha acesso à inscrição
     */
    public function storeDisciplina(Request $request, Inscricao $inscricao)
    {
        Gate::authorize('inscricoes.update', $inscricao);

        $request->validate([
            'id' => 'required',
        ],
        [
            'id.required' => 'Disciplina obrigatória',
        ]);

        // transaction para não ter problema de inconsistência do DB
        $db_transaction = DB::transaction(function () use ($request, $inscricao) {

            $info_adicional = '';
            $disciplina = Disciplina::where('id', $request->id)->first();

            $extras = json_decode($inscricao->extras, true);
            $disciplinas_id = (isset($extras['disciplinas']) ? $extras['disciplinas'] : []);
            $existia = is_array($disciplinas_id) && in_array($request->id, $disciplinas_id);

            if (!$existia) {
                $extras['disciplinas'][] = $request->id;
                $inscricao->extras = json_encode($extras);

                if (Parametro::first()->boleto_momento_envio == 'Envio da Inscrição/Matrícula')
                    // se já havia enviado a inscrição, avisa para reenviá-la
                    if ($inscricao->estado == 'Enviada') {
                        $inscricao->estado = 'Aguardando Envio';
                        $info_adicional = '<br />Reenvie esta inscrição para gerar ' . ((count($extras['disciplinas']) == 1) ? 'novo boleto' : 'novos boletos');
                    }

                $inscricao->save();
            }

            return ['disciplina' => $disciplina, 'existia' => $existia, 'info_adicional' => $info_adicional];
        });

        if (!$db_transaction['existia'])
            $request->session()->flash('alert-success', 'A disciplina ' . $db_transaction['disciplina']->sigla . ' - ' . $db_transaction['disciplina']->nome . ' foi adicionada à essa inscrição.' . $db_transaction['info_adicional']);
        else
            $request->session()->flash('alert-info', 'A disciplina ' . $db_transaction['disciplina']->sigla . ' - ' . $db_transaction['disciplina']->nome . ' já estava vinculada à essa inscrição.');
        \UspTheme::activeUrl('inscricoes');
        return redirect()->to(url('inscricoes/edit/' . $inscricao->id))->with($this->monta_compact($inscricao, 'edit', 'disciplinas'));    // se fosse return view, um eventual F5 do usuário duplicaria o registro... POSTs devem ser com redirect
    }

    /**
     * Remove uma disciplina relacionada à inscrição
     */
    public function destroyDisciplina(Request $request, Inscricao $inscricao, Disciplina $disciplina)
    {
        Gate::authorize('inscricoes.update', $inscricao);

        $info_adicional = '';

        $extras = json_decode($inscricao->extras, true);
        $disciplinas_id = (isset($extras['disciplinas']) ? $extras['disciplinas'] : []);
        $indice = array_search($disciplina->id, $disciplinas_id);

        if ($indice !== false) {
            unset($extras['disciplinas'][$indice]);
            $inscricao->extras = json_encode($extras);

            if (Parametro::first()->boleto_momento_envio == 'Envio da Inscrição/Matrícula')
                // se já havia enviado a inscrição, avisa para reenviá-la
                if ($inscricao->estado == 'Enviada') {
                    $inscricao->estado = 'Aguardando Envio';
                    $info_adicional = '<br />Reenvie esta inscrição para gerar ' . ((count($extras['disciplinas']) == 1) ? 'novo boleto' : 'novos boletos');
                }

            $inscricao->save();
        }

        $request->session()->flash('alert-success', 'A disciplina ' . $disciplina->sigla . ' - '. $disciplina->nome . ' foi removida dessa inscrição.' . $info_adicional);
        \UspTheme::activeUrl('inscricoes');
        return redirect()->to(url('inscricoes/edit/' . $inscricao->id))->with($this->monta_compact($inscricao, 'edit', 'disciplinas'));    // se fosse return view, um eventual F5 do usuário duplicaria o registro... POSTs devem ser com redirect
    }

    /**
     * Gera o(s) boleto(s) para a inscrição - usado por admins para gerar manualmente o(s) boleto(s), caso necessário
     */
    public function geraBoletos(Request $request, Inscricao $inscricao)
    {
        if ($inscricao->selecao->categoria->nome !== 'Aluno Especial') {
            // gera o boleto da inscrição
            if (empty($this->boletoService->gerarBoleto($inscricao, 'Inscricao')['nome_original'])) {
                $request->session()->flash('alert-danger', 'Não foi possível gerar o boleto para essa inscrição.');
                \UspTheme::activeUrl('inscricoes');
                return redirect()->to(url('inscricoes/edit/' . $inscricao->id))->with($this->monta_compact($inscricao, 'edit'));    // se fosse return view, um eventual F5 do usuário duplicaria o registro... POSTs devem ser com redirect
            }
        } else
            // gera um boleto para cada disciplina solicitada
            foreach ($request->disciplinas as $sigla => $valor)
                if (empty($this->boletoService->gerarBoleto($inscricao, 'Inscricao', $sigla)['nome_original'])) {
                    $request->session()->flash('alert-danger', 'Não foi possível gerar o boleto da disciplina ' . $sigla . ' para essa inscrição<br />' .
                        'A geração do(s) boleto(s) foi abortada');
                    \UspTheme::activeUrl('inscricoes');
                    return redirect()->to(url('inscricoes/edit/' . $inscricao->id))->with($this->monta_compact($inscricao, 'edit'));
                }

        $request->session()->flash('alert-success', ($inscricao->selecao->categoria->nome !== 'Aluno Especial' ? 'O boleto foi gerado com sucesso' : 'O(s) boleto(s) foi(ram) gerado(s) com sucesso'));
        \UspTheme::activeUrl('inscricoes');
        return redirect()->to(url('inscricoes/edit/' . $inscricao->id))->with($this->monta_compact($inscricao, 'edit', 'arquivos'));
    }

    /**
     * Envia um boleto da inscrição
     */
    public function enviaBoleto(Request $request, Inscricao $inscricao, Arquivo $arquivo)
    {
        if (!$arquivo || !$arquivo->inscricoes->contains($inscricao)) {
            $request->session()->flash('alert-danger', 'Esse documento não existe ou não pertence a essa inscrição');
            \UspTheme::activeUrl('inscricoes');
            return redirect()->to(url('inscricoes/edit/' . $inscricao->id))->with($this->monta_compact($inscricao, 'edit'));
        }

        // envia e-mail para o candidato com o boleto
        // envio do e-mail "15" do README.md
        $passo = 'boleto - envio manual';
        $user = $inscricao->pessoas('Autor');
        $arquivo->conteudo = base64_encode(Storage::get($arquivo->caminho));
        \Mail::to($user->email)
            ->queue(new InscricaoMail(compact('passo', 'inscricao', 'user', 'arquivo')));

        $request->session()->flash('alert-success', 'O boleto foi enviado com sucesso');
        \UspTheme::activeUrl('inscricoes');
        return redirect()->to(url('inscricoes/edit/' . $inscricao->id))->with($this->monta_compact($inscricao, 'edit', 'arquivos'));
    }

    public function monta_compact_index()
    {
        $data = self::$data;
        $objetos = Inscricao::listarInscricoes();
        foreach ($objetos as $objeto) {
            $extras = json_decode($objeto->extras, true);
            $objeto->linha_pesquisa = (isset($extras['linha_pesquisa']) ? (LinhaPesquisa::where('id', $extras['linha_pesquisa'])->first()->nome ?? null) : null);
            $objeto->disciplinas = (isset($extras['disciplinas']) ? (Disciplina::whereIn('id', $extras['disciplinas'])->orderBy('sigla')->get()->map(function ($disciplina) {
                return $disciplina->sigla . ' - ' . $disciplina->nome;
            })->implode(',<br />')) : null);
        }
        $classe_nome = 'Inscricao';
        $max_upload_size = config('selecoes-pos.upload_max_filesize');
        $niveis = Nivel::all();

        return compact('data', 'objetos', 'classe_nome', 'max_upload_size', 'niveis');
    }

    public function monta_compact(Inscricao $inscricao, string $modo, ?string $scroll = null)
    {
        $data = (object) self::$data;
        $inscricao->selecao->template_inscricoes = JSONForms::orderTemplate($inscricao->selecao->template_inscricoes);
        $objeto = $inscricao;
        $classe_nome = 'Inscricao';
        $classe_nome_plural = 'inscricoes';
        $form = JSONForms::generateForm($objeto->selecao, $classe_nome, $objeto);
        $responsaveis = $objeto->selecao->programa?->obterResponsaveis() ?? (new Programa())->obterResponsaveis();
        $extras = json_decode($objeto->extras, true);
        $objeto_disciplinas = ((isset($extras['disciplinas']) && is_array($extras['disciplinas'])) ? Disciplina::whereIn('id', $extras['disciplinas'])->orderBy('sigla')->get() : collect());
        $disciplinas = Disciplina::obterDisciplinasPossiveis($objeto->selecao);
        $nivel = (isset($extras['nivel']) ? Nivel::where('id', $extras['nivel'])->first()->nome : '');
        $objeto->tiposarquivo = TipoArquivo::obterTiposArquivoDaSelecao('Inscricao', ($objeto->selecao->categoria?->nome == 'Aluno Especial' ? new Collection() : collect([['nome' => $nivel]])), $objeto->selecao)
            ->filter(function ($tipoarquivo) use ($inscricao) { return (!str_starts_with($tipoarquivo->nome, 'Boleto(s) de Pagamento')) || $inscricao->selecao->tem_taxa; })
            ->sortBy(function ($tipoarquivo) { return str_starts_with($tipoarquivo->nome, 'Boleto(s) de Pagamento') ? 1 : 0; });
        $tiposarquivo_selecao = TipoArquivo::obterTiposArquivoPossiveis('Selecao', null, $objeto->selecao->programa_id)
            ->filter(function ($tipoarquivo) use ($inscricao) { return ($tipoarquivo->nome !== 'Normas para Isenção de Taxa') || $inscricao->selecao->tem_taxa; });
        $solicitacaoisencaotaxa_aprovada = SolicitacaoIsencaoTaxa::where('extras->cpf', $extras['cpf'] ?? null)
                                                                 ->where('selecao_id', $objeto->selecao->id)
                                                                 ->where('estado', 'LIKE', 'Isenção de Taxa Aprovada%')->first();
        $disciplinas_sem_boleto = [];
        if ($inscricao->selecao->categoria->nome == 'Aluno Especial')
            foreach ($objeto_disciplinas as $disciplina)
                if ($inscricao->arquivos->filter(fn($a) => ($a->pivot->tipo == 'Boleto(s) de Pagamento') && str_contains(strtolower($a->nome_original), strtolower($disciplina->sigla)))->count() == 0)
                    $disciplinas_sem_boleto[] = $disciplina;
        $inscricao->disciplinas_sem_boleto = $disciplinas_sem_boleto;
        $boleto_momento_envio = Parametro::first()->boleto_momento_envio;
        $max_upload_size = config('selecoes-pos.upload_max_filesize');

        return compact('data', 'objeto', 'classe_nome', 'classe_nome_plural', 'form', 'modo', 'responsaveis', 'objeto_disciplinas', 'disciplinas', 'nivel', 'tiposarquivo_selecao', 'solicitacaoisencaotaxa_aprovada', 'boleto_momento_envio', 'max_upload_size', 'scroll');
    }
}
