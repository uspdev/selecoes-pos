<?php

namespace App\Http\Controllers;

use App\Http\Requests\SelecaoRequest;
use App\Jobs\AtualizaStatusSelecoes;
use App\Models\Categoria;
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
use App\Utils\ClasseUtils;
use App\Utils\JSONForms;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Spatie\SimpleExcel\SimpleExcelWriter;

class SelecaoController extends Controller
{
    // crud generico
    public static $data = [
        'title' => 'Seleções',
        'url' => 'selecoes',     // caminho da rota do resource
        'modal' => true,
        'showId' => false,
        'viewBtn' => true,
        'editBtn' => false,
        'model' => 'App\Models\Selecao',
    ];

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        Gate::authorize('selecoes.viewAny');

        \UspTheme::activeUrl('selecoes');
        AtualizaStatusSelecoes::dispatch()->onConnection('sync');
        $data = self::$data;
        $objetos = Selecao::listarSelecoes();
        $classe_nome = 'Selecao';
        $max_upload_size = config('selecoes-pos.upload_max_filesize');
        return view('selecoes.index', compact('data', 'objetos', 'classe_nome', 'max_upload_size'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        Gate::authorize('selecoes.create');

        \UspTheme::activeUrl('selecoes');
        return view('selecoes.edit', $this->monta_compact(new Selecao, 'create'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\SelecaoRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(SelecaoRequest $request)
    {
        Gate::authorize('selecoes.create');

        $selecaoRequest = new SelecaoRequest();
        $validator = Validator::make($request->all(), $selecaoRequest->rules(), $selecaoRequest->messages());
        if ($validator->fails()) {
            \UspTheme::activeUrl('selecoes');
            return back()->withErrors($validator)->withInput();
        }

        $request->merge(['fluxo_continuo' => $request->has('fluxo_continuo')]);    // acerta o valor do campo "fluxo_continuo" (pois, se o usuário deixou false, o campo não vem no $request e, se o usuário deixou true, ele vem mas com valor null)
        $request->merge(['tem_taxa' => $request->has('tem_taxa')]);                // acerta o valor do campo "tem_taxa"       (pois, se o usuário deixou false, o campo não vem no $request e, se o usuário deixou true, ele vem mas com valor null)
        $request->merge(['boleto_valor' => ($request->input('boleto_valor') !== '' ? $request->input('boleto_valor') : null)]);
        $request->merge(['boleto_offset_vencimento' => ($request->input('boleto_offset_vencimento') !== '' ? $request->input('boleto_offset_vencimento') : null)]);

        $requestData = $request->all();
        $requestData['solicitacoesisencaotaxa_datahora_inicio'] = (is_null($requestData['solicitacoesisencaotaxa_data_inicio']) || is_null($requestData['solicitacoesisencaotaxa_hora_inicio']) ? null : Carbon::createFromFormat('d/m/Y H:i', $requestData['solicitacoesisencaotaxa_data_inicio'] . ' ' . $requestData['solicitacoesisencaotaxa_hora_inicio']));
        $requestData['solicitacoesisencaotaxa_datahora_fim'] = (is_null($requestData['solicitacoesisencaotaxa_data_fim']) || is_null($requestData['solicitacoesisencaotaxa_hora_fim']) ? null : Carbon::createFromFormat('d/m/Y H:i', $requestData['solicitacoesisencaotaxa_data_fim'] . ' ' . $requestData['solicitacoesisencaotaxa_hora_fim']));
        $requestData['inscricoesmatriculas_datahora_inicio'] = (is_null($requestData['inscricoesmatriculas_data_inicio']) || is_null($requestData['inscricoesmatriculas_hora_inicio']) ? null : Carbon::createFromFormat('d/m/Y H:i', $requestData['inscricoesmatriculas_data_inicio'] . ' ' . $requestData['inscricoesmatriculas_hora_inicio']));
        $requestData['inscricoesmatriculas_datahora_fim'] = (is_null($requestData['inscricoesmatriculas_data_fim']) || is_null($requestData['inscricoesmatriculas_hora_fim']) ? null : Carbon::createFromFormat('d/m/Y H:i', $requestData['inscricoesmatriculas_data_fim'] . ' ' . $requestData['inscricoesmatriculas_hora_fim']));
        $requestData['boleto_valor'] = (is_null($requestData['boleto_valor']) ? null : str_replace(',', '.', $requestData['boleto_valor']));
        $requestData['boleto_data_vencimento'] = (is_null($requestData['boleto_data_vencimento']) ? null : Carbon::createFromFormat('d/m/Y', $requestData['boleto_data_vencimento'])->endOfDay());
        $requestData['boleto_offset_vencimento'] = (is_null($requestData['boleto_offset_vencimento']) ? null : $requestData['boleto_offset_vencimento']);

        if (!$this->validateDates($requestData['solicitacoesisencaotaxa_datahora_inicio']?->toDateTime(), $requestData['solicitacoesisencaotaxa_datahora_fim']?->toDateTime(), $requestData['inscricoesmatriculas_datahora_inicio']?->toDateTime(), $requestData['inscricoesmatriculas_datahora_fim']?->toDateTime(), $requestData['boleto_data_vencimento']?->toDateTime())) {
            $request->session()->flash('alert-danger', 'Datas/horas de início e fim dos períodos e data de vencimento do boleto estão inconsistentes');
            \UspTheme::activeUrl('selecoes');
            return back()->withInput();
        }

        // transaction para não ter problema de inconsistência do DB
        $db_transaction = DB::transaction(function () use ($requestData) {

            $selecao = Selecao::create($requestData);
            $selecao->atualizarStatus();
            $selecao->estado = Selecao::where('id', $selecao->id)->value('estado');

            $classes_nome = [];
            if (!$selecao->fazInscricoes()) {
                $selecao->template_inscricoes = '{}';
                $classes_nome[] = 'Inscrições';
            }
            if (!$selecao->fazMatriculas()) {
                $selecao->template_matriculas = '{}';
                $classes_nome[] = 'Matrículas';
            }

            // obtém a última seleção desse programa/aluno especial
            if ($selecao->categoria->nome != 'Aluno Especial')
                $queryUltimaSelecao = Selecao::where('programa_id', $selecao->programa_id)->where('id', '!=', $selecao->id);
            else
                $queryUltimaSelecao = Selecao::whereRelation('categoria', 'nome', 'Aluno Especial')->where('id', '!=', $selecao->id);
            $ultimaSelecao = $queryUltimaSelecao->orderBy('id', 'desc')->first();

            if ($ultimaSelecao) {
                // herda vários dados da última seleção
                if ($selecao->categoria->nome != 'Aluno Especial')
                    $selecao->niveislinhaspesquisa()->attach($ultimaSelecao->niveislinhaspesquisa->pluck('id'));
                if ($selecao->fazInscricoes()) {
                    $selecao->template_inscricoes = $ultimaSelecao->template_inscricoes;
                    $selecao->tiposarquivo()->attach($ultimaSelecao->tiposarquivo->where('classe_nome', 'Inscrições')->pluck('id'));
                }
                if ($selecao->fazMatriculas()) {
                    $selecao->template_matriculas = $ultimaSelecao->template_matriculas;
                    $selecao->tiposarquivo()->attach($ultimaSelecao->tiposarquivo->where('classe_nome', 'Matrículas')->pluck('id'));
                }
            } else {
                // cadastra automaticamente todas as instâncias de vários objetos como possíveis para este processo seletivo
                if ($selecao->categoria->nome != 'Aluno Especial')
                    $selecao->niveislinhaspesquisa()->attach(NivelLinhaPesquisa::whereRelation('linhapesquisa', 'programa_id', $selecao->programa_id)->pluck('id'));
                if ($selecao->fazInscricoes())
                    $selecao->tiposarquivo()->attach(TipoArquivo::where('classe_nome', 'Inscrições')->whereHas('categorias', function ($query) use ($selecao) { $query->where('nome', $selecao->categoria->nome); })->whereRelation('niveisprogramas', 'programa_id', $selecao->programa_id)->pluck('id'));
                if ($selecao->fazMatriculas())
                    $selecao->tiposarquivo()->attach(TipoArquivo::where('classe_nome', 'Matrículas')->whereHas('categorias', function ($query) use ($selecao) { $query->where('nome', $selecao->categoria->nome); })->pluck('id'));
            }

            if ($selecao->tem_taxa) {
                // obtém a última seleção com taxa desse programa/aluno especial
                $ultimaSelecaoComTaxa = (clone $queryUltimaSelecao)->where('tem_taxa', true)->orderBy('id', 'desc')->first();

                if ($ultimaSelecaoComTaxa) {
                    // herda vários dados da última seleção
                    $selecao->template_solicitacoesisencaotaxa = $ultimaSelecaoComTaxa->template_solicitacoesisencaotaxa;
                    $selecao->motivosisencaotaxa()->attach($ultimaSelecaoComTaxa->motivosisencaotaxa->pluck('id'));
                    $selecao->tiposarquivo()->attach($ultimaSelecaoComTaxa->tiposarquivo->where('classe_nome', 'Solicitações de Isenção de Taxa')->pluck('id'));
                } else {
                    // cadastra automaticamente todas as instâncias de vários objetos como possíveis para este processo seletivo
                    $selecao->motivosisencaotaxa()->attach(MotivoIsencaoTaxa::listarMotivosIsencaoTaxa()->pluck('id'));
                    $selecao->tiposarquivo()->attach(TipoArquivo::where('classe_nome', 'Solicitações de Isenção de Taxa')->pluck('id'));
                }

                // se os tipos de documento de boletos não foram cadastrados na seleção, força seu cadastramento
                $tiposarquivo_boletoIds = TipoArquivo::whereIn('classe_nome', $classes_nome)->whereIn('nome', ['Boleto(s) de Pagamento', 'Boleto(s) de Pagamento - Disciplinas Removidas'])->pluck('id');
                if ($tiposarquivo_boletoIds->isNotEmpty())
                    $selecao->tiposarquivo()->syncWithoutDetaching($tiposarquivo_boletoIds);    // adiciona os registros ausentes sem remover os já existentes
            } else
                $selecao->template_solicitacoesisencaotaxa = '{}';
            $selecao->save();    // necessário devido às eventuais alterações nos templates acima... se nenhum template tiver sido modificado, o Laravel ignora este comando

            $selecao->reagendarTarefas();

            return ['selecao' => $selecao];
        });
        $selecao = $db_transaction['selecao'];

        $request->session()->flash('alert-success', 'Seleção cadastrada com sucesso<br />' .
            'Agora ' . ($selecao->fazInscricoes() ? 'informe quais são as linhas de pesquisa e ' : '') . 'adicione os informativos relacionados ao processo');
        \UspTheme::activeUrl('selecoes');
        return redirect()->to(url('selecoes/edit/' . $selecao->id))->with($this->monta_compact($selecao, 'edit'));    // se fosse return view, um eventual F5 do usuário duplicaria o registro... POSTs devem ser com redirect
    }

    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request   $request
     * @param  \App\Models\Selecao        $selecao
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, Selecao $selecao)
    {
        Gate::authorize('selecoes.update', $selecao);

        $selecao->atualizarStatus();

        \UspTheme::activeUrl('selecoes');
        return view('selecoes.edit', $this->monta_compact($selecao, 'edit', session('scroll')));    // repassa scroll que eventualmente veio de redirect()->to(url(
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\SelecaoRequest  $request
     * @param  \App\Models\Selecao                $selecao
     * @return \Illuminate\Http\Response
     */
    public function update(SelecaoRequest $request, Selecao $selecao)
    {
        Gate::authorize('selecoes.update', $selecao);

        $selecaoRequest = new SelecaoRequest();
        $validator = Validator::make($request->all(), $selecaoRequest->rules(), $selecaoRequest->messages());
        if ($validator->fails()) {
            \UspTheme::activeUrl('selecoes');
            return view('selecoes.edit', $this->monta_compact($selecao, 'edit'))->withErrors($validator);    // preciso especificar 'edit'... se eu fizesse um return back(), e o usuário estivesse vindo de um update após um create, a variável $modo voltaria a ser 'create', e a página ficaria errada
        }

        $requestData = $request->all();
        $requestData['solicitacoesisencaotaxa_datahora_inicio'] = (is_null($requestData['solicitacoesisencaotaxa_data_inicio']) || is_null($requestData['solicitacoesisencaotaxa_hora_inicio']) ? null : Carbon::createFromFormat('d/m/Y H:i', $requestData['solicitacoesisencaotaxa_data_inicio'] . ' ' . $requestData['solicitacoesisencaotaxa_hora_inicio']));
        $requestData['solicitacoesisencaotaxa_datahora_fim'] = (is_null($requestData['solicitacoesisencaotaxa_data_fim']) || is_null($requestData['solicitacoesisencaotaxa_hora_fim']) ? null : Carbon::createFromFormat('d/m/Y H:i', $requestData['solicitacoesisencaotaxa_data_fim'] . ' ' . $requestData['solicitacoesisencaotaxa_hora_fim']));
        $requestData['inscricoesmatriculas_datahora_inicio'] = (is_null($requestData['inscricoesmatriculas_data_inicio']) || is_null($requestData['inscricoesmatriculas_hora_inicio']) ? null : Carbon::createFromFormat('d/m/Y H:i', $requestData['inscricoesmatriculas_data_inicio'] . ' ' . $requestData['inscricoesmatriculas_hora_inicio']));
        $requestData['inscricoesmatriculas_datahora_fim'] = (is_null($requestData['inscricoesmatriculas_data_fim']) || is_null($requestData['inscricoesmatriculas_hora_fim']) ? null : Carbon::createFromFormat('d/m/Y H:i', $requestData['inscricoesmatriculas_data_fim'] . ' ' . $requestData['inscricoesmatriculas_hora_fim']));
        $requestData['boleto_data_vencimento'] = (is_null($requestData['boleto_data_vencimento']) ? null : Carbon::createFromFormat('d/m/Y', $requestData['boleto_data_vencimento'])->endOfDay());

        if (!$this->validateDates($requestData['solicitacoesisencaotaxa_datahora_inicio']?->toDateTime(), $requestData['solicitacoesisencaotaxa_datahora_fim']?->toDateTime(), $requestData['inscricoesmatriculas_datahora_inicio']?->toDateTime(), $requestData['inscricoesmatriculas_datahora_fim']?->toDateTime(), $requestData['boleto_data_vencimento']?->toDateTime())) {
            $request->session()->flash('alert-danger', 'Datas/horas de início e fim dos períodos e data de vencimento do boleto estão inconsistentes');
            \UspTheme::activeUrl('selecoes');
            return back()->withInput();
        }

        if (($selecao->programa_id != $request->programa_id) && !empty($request->programa_id))
            if ($selecao->linhaspesquisa->count() > 0) {
                $request->session()->flash('alert-danger', 'Não se pode alterar o programa, pois há linhas de pesquisa/temas do programa antigo cadastrados para esta seleção!');
                \UspTheme::activeUrl('selecoes');
                return view('selecoes.edit', $this->monta_compact($selecao, 'edit'));
            }

        $request->merge(['fluxo_continuo' => $request->has('fluxo_continuo')]);    // acerta o valor do campo "fluxo_continuo" (pois, se o usuário deixou false, o campo não vem no $request e, se o usuário deixou true, ele vem mas com valor null)
        $request->merge(['tem_taxa' => $request->has('tem_taxa')]);                // acerta o valor do campo "tem_taxa"       (pois, se o usuário deixou false, o campo não vem no $request e, se o usuário deixou true, ele vem mas com valor null)
        $request->merge(['boleto_valor' => ($request->input('boleto_valor') !== '' ? $request->input('boleto_valor') : null)]);
        $request->merge(['boleto_offset_vencimento' => ($request->input('boleto_offset_vencimento') !== '' ? $request->input('boleto_offset_vencimento') : null)]);

        // transaction para não ter problema de inconsistência do DB
        $selecao = DB::transaction(function () use ($request, $selecao) {

            $selecao->programa_id = $request->programa_id;
            $this->updateField($request, $selecao, 'categoria_id', 'categoria', 'a');
            $this->updateField($request, $selecao, 'ingresso_semestre', 'semestre de ingresso', 'o');
            $this->updateField($request, $selecao, 'ingresso_ano', 'ano de ingresso', 'o');
            $this->updateField($request, $selecao, 'descricao', 'descrição', 'a');
            $this->updateField($request, $selecao, 'solicitacoesisencaotaxa_datahora_inicio', 'data/hora início solicitações de isenção de taxa', 'a');
            $this->updateField($request, $selecao, 'solicitacoesisencaotaxa_datahora_fim', 'data/hora fim solicitações de isenção de taxa', 'a');
            $this->updateField($request, $selecao, 'inscricoesmatriculas_datahora_inicio', 'data/hora início inscrições/matrículas', 'a');
            $this->updateField($request, $selecao, 'inscricoesmatriculas_datahora_fim', 'data/hora fim inscrições/matrículas', 'a');
            $this->updateField($request, $selecao, 'fluxo_continuo', 'fluxo contínuo', 'o');
            $this->updateField($request, $selecao, 'tem_taxa', 'taxa de inscrição/matrícula', 'a');
            $this->updateField($request, $selecao, 'boleto_valor', 'valor do boleto', 'o');
            $this->updateField($request, $selecao, 'boleto_texto', 'eventuais informações adicionais no boleto', 'o');
            $this->updateField($request, $selecao, 'boleto_data_vencimento', 'data de vencimento do boleto', 'a');
            $this->updateField($request, $selecao, 'boleto_offset_vencimento', 'quantidade de dias úteis para pagamento do boleto', 'a');
            $this->updateField($request, $selecao, 'email_inscricaomatriculaaprovacao_texto', 'eventuais informações adicionais no e-mail de aprovação da inscrição/matrícula', 'o');
            $this->updateField($request, $selecao, 'email_inscricaomatricularejeicao_texto', 'eventuais informações adicionais no e-mail de rejeição da inscrição/matrícula', 'o');
            $selecao->save();

            $selecao->atualizarStatus();
            $selecao->estado = Selecao::where('id', $selecao->id)->value('estado');

            $selecao->reagendarTarefas();

            return $selecao;
        });

        $request->session()->flash('alert-success', 'Seleção alterada com sucesso');
        \UspTheme::activeUrl('selecoes');
        return view('selecoes.edit', $this->monta_compact($selecao, 'edit'));
    }

    private function validateDates(?\DateTime $solicitacoesisencaotaxa_datahora_inicio, ?\DateTime $solicitacoesisencaotaxa_datahora_fim, \DateTime $inscricoesmatriculas_datahora_inicio, \DateTime $inscricoesmatriculas_datahora_fim, ?\DateTime $boleto_data_vencimento)
    {
        $boleto_data_vencimento = $boleto_data_vencimento ?? CarbonImmutable::endOfTime();

        if (!is_null($solicitacoesisencaotaxa_datahora_inicio) && !is_null($solicitacoesisencaotaxa_datahora_fim))
            return ($solicitacoesisencaotaxa_datahora_inicio < $solicitacoesisencaotaxa_datahora_fim) &&
                   ($solicitacoesisencaotaxa_datahora_fim < $inscricoesmatriculas_datahora_inicio) &&
                   ($inscricoesmatriculas_datahora_inicio < $inscricoesmatriculas_datahora_fim) &&
                   ($inscricoesmatriculas_datahora_fim < $boleto_data_vencimento);
        else
            return ($inscricoesmatriculas_datahora_inicio < $inscricoesmatriculas_datahora_fim) &&
                   ($inscricoesmatriculas_datahora_fim < $boleto_data_vencimento);
    }

    private function updateField(SelecaoRequest $request, Selecao $selecao, string $field, string $field_name, string $genero)
    {
        if ((strpos($field, 'datahora_') === 0) || (strpos($field, '_datahora_') !== false)) {
            $request->$field = (is_null($request->{str_replace('datahora_', 'data_', $field)}) ||
                                is_null($request->{str_replace('datahora_', 'hora_', $field)}) ? null : Carbon::createFromFormat('d/m/Y H:i', $request->{str_replace('datahora_', 'data_', $field)} .
                                                                                                                                              ' ' .
                                                                                                                                              $request->{str_replace('datahora_', 'hora_', $field)})->format('Y-m-d H:i'));
        }

        if ((strpos($field, 'data_') === 0) || (strpos($field, '_data_') !== false))
            $request->$field = (is_null($request->$field) ? null : Carbon::createFromFormat('d/m/Y', $request->$field)->format('Y-m-d'));

        if ($selecao->$field != $request->$field)
            $selecao->$field = $request->$field;
    }

    public function storeTemplateJson(Request $request, Selecao $selecao, string $classe_nome)
    {
        Gate::authorize('selecoes.update', $selecao);

        \UspTheme::activeUrl('selecoes');
        $classe_nome_plural = ClasseUtils::obterClasseNomePlural($classe_nome);
        $newjson = $request->{'template_' . $classe_nome_plural};
        $selecao->{'template_' . $classe_nome_plural} = $newjson;
        $selecao->save();
        $request->session()->flash('alert-success', 'Template salvo com sucesso');
        return redirect()->to(url('selecoes/edit/' . $selecao->id))->with($this->monta_compact($selecao, 'edit', 'formulario'));    // se fosse return view, um eventual F5 do usuário duplicaria o registro... POSTs devem ser com redirect
    }

    public function createTemplate(Selecao $selecao, string $classe_nome)
    {
        Gate::authorize('selecoes.update', $selecao);

        \UspTheme::activeUrl('selecoes');
        $classe_nome_plural = ClasseUtils::obterClasseNomePlural($classe_nome);
        $template = json_decode(JSONForms::orderTemplate($selecao->{'template_' . $classe_nome_plural}), true);
        return view('selecoes.template', compact('selecao', 'classe_nome', 'template'));
    }

    public function storeTemplate(Request $request, Selecao $selecao, string $classe_nome)
    {
        Gate::authorize('selecoes.update', $selecao);

        $request->validate([
            'template.*.label' => 'required',
            'template.*.type' => 'required',
        ]);
        if (isset($request->campo)) {
            $request->validate([
                'new.label' => 'required',
                'new.type' => 'required',
            ]);
        }
        $classe_nome_plural = ClasseUtils::obterClasseNomePlural($classe_nome);
        $template = [];
        // remonta $template, considerando apenas o que veio do $request (com isso, atualiza e também apaga)
        if (isset($request->template))
            foreach ($request->template as $campo => $atributos)
                $template[$campo] = array_filter($atributos, 'strlen');
        // trata campo do tipo select
        foreach ($template as $campo => $atributo)
            if (($atributo['type'] == 'select') || ($atributo['type'] == 'radio'))
                $template[$campo]['value'] = isset($atributo['value']) ? json_decode($atributo['value'], true) : [];
        // adiciona campo novo
        $new = (!is_null($request->new)) ? array_filter($request->new, 'strlen') : null;
        if (isset($new['label']))
            $new['label'] = removeSpecialChars($new['label']);
        $new['order'] = JSONForms::getLastIndex($template, 'order') + 1;
        if (isset($request->campo)) {                           // veio do adicionar campo novo
            $request->campo = removeSpecialChars($request->campo);
            $template[$request->campo] = $new;
            if (isset($new['value']))
                $template[$request->campo]['value'] = json_decode($new['value']);    // necessário para remover " excedentes que quebravam o JSON
            elseif ($template[$request->campo]['type'] == 'select')
                $template[$request->campo]['value'] = '[]';
        }
        $selecao->{'template_' . $classe_nome_plural} = JSONForms::fixJson($template);
        $selecao->save();

        $request->session()->flash('alert-success', 'Formulário salvo com sucesso');
        \UspTheme::activeUrl('selecoes');
        $template = json_decode(JSONForms::orderTemplate($selecao->{'template_' . $classe_nome_plural}), true);
        return redirect()->to(url('selecoes/' . $selecao->id . '/' . $classe_nome . '/template'))->with(compact('selecao', 'template'));    // se fosse return view, um eventual F5 do usuário duplicaria o registro... POSTs devem ser com redirect
    }

    public function createTemplateValue(Selecao $selecao, string $classe_nome, string $field)
    {
        Gate::authorize('selecoes.update', $selecao);

        \UspTheme::activeUrl('selecoes');
        $classe_nome_plural = ClasseUtils::obterClasseNomePlural($classe_nome);
        $template = json_decode(JSONForms::orderTemplate($selecao->{'template_' . $classe_nome_plural}), true);
        return view('selecoes.templatevalue', compact('selecao', 'classe_nome', 'template', 'field'));
    }

    public function storeTemplateValue(Request $request, Selecao $selecao, string $classe_nome, string $field)
    {
        Gate::authorize('selecoes.update', $selecao);

        $request->validate([
            'value.*.label' => 'required',
        ]);
        $new = (!is_null($request->new)) ? array_filter($request->new, 'strlen') : null;
        if (is_array($new) && !empty($new)) {                           // veio do adicionar campo novo
            $request->validate([
                'new.label' => 'required',
            ]);
        }
        $classe_nome_plural = ClasseUtils::obterClasseNomePlural($classe_nome);
        $template = json_decode($selecao->{'template_' . $classe_nome_plural});
        $value = [];
        // remonta $value, considerando apenas o que veio do $request (com isso, atualiza e também apaga)
        if (isset($request->value)) {
            foreach ($request->value as $campo => $atributos) {
                $atributos['label'] = removeSpecialChars($atributos['label']);
                $atributos['value'] = substr(removeAccents(Str::of($atributos['label'])->lower()->replace([' ', '-'], '_')), 0, 32);
                $value[$campo] = array_filter($atributos, 'strlen');
            }
        }
        // adiciona campo novo
        if (is_array($new) && !empty($new)) {                           // veio do adicionar campo novo
            $new['label'] = removeSpecialChars($new['label']);
            $new['value'] = substr(removeAccents(Str::of($new['label'])->lower()->replace([' ', '-'], '_')), 0, 32);
            $new['order'] = JSONForms::getLastIndex(array_values((array) $template->$field->value) ?? [], 'order') + 1;
            $value[] = $new;
        }
        $template->$field->value = $value;
        $template = json_decode(json_encode($template), true);
        $selecao->{'template_' . $classe_nome_plural} = JSONForms::fixJson($template, true);
        $selecao->save();

        $request->session()->flash('alert-success', 'Lista salva com sucesso');
        \UspTheme::activeUrl('selecoes');
        return redirect()->to(url('selecoes/' . $selecao->id . '/' . $classe_nome . '/templatevalue/' . $field))->with(compact('selecao', 'template', 'field'));    // se fosse return view, um eventual F5 do usuário duplicaria o registro... POSTs devem ser com redirect
    }

    /**
     * Adicionar combinações de linhas de pesquisa/temas com níveis relacionados à seleção
     * autorizado a qualquer um que tenha acesso à seleção
     * request->codpes = required, int
     */
    public function storeNiveisLinhasPesquisa(Request $request, Selecao $selecao)
    {
        Gate::authorize('selecoes.update', $selecao);

        $request->validate([
            'id' => 'required|array',
        ],
        [
            'id.required' => 'Combinações de nível com linha de pesquisa/tema obrigatórias',
        ]);

        // transaction para não ter problema de inconsistência do DB
        $db_transaction = DB::transaction(function () use ($request, $selecao) {

            $niveislinhaspesquisa = NivelLinhaPesquisa::whereIn('id', $request->id)->get();

            foreach ($niveislinhaspesquisa as $nivellinhapesquisa) {
                $selecao->niveislinhaspesquisa()->detach($nivellinhapesquisa);
                $selecao->niveislinhaspesquisa()->attach($nivellinhapesquisa);
            }

            $selecao->atualizarStatus();
        });

        $request->session()->flash('alert-info', 'As combinações níveis com linhas de pesquisa/temas foram alteradas nessa seleção.');
        \UspTheme::activeUrl('selecoes');
        return redirect()->to(url('selecoes/edit/' . $selecao->id))->with($this->monta_compact($selecao, 'edit', 'niveislinhaspesquisa'));    // se fosse return view, um eventual F5 do usuário duplicaria o registro... POSTs devem ser com redirect
    }

    /**
     * Remove combinações de níveis com linhas de pesquisa/temas relacionadas à seleção
     * $user = required
     */
    public function destroyNivelLinhaPesquisa(Request $request, Selecao $selecao, NivelLinhaPesquisa $nivellinhapesquisa)
    {
        Gate::authorize('selecoes.update', $selecao);

        // transaction para não ter problema de inconsistência do DB
        $db_transaction = DB::transaction(function () use ($selecao, $nivellinhapesquisa) {

            $selecao->niveislinhaspesquisa()->detach($nivellinhapesquisa);
            $selecao->atualizarStatus();
        });

        $request->session()->flash('alert-success', 'A combinação nível ' . $nivellinhapesquisa->nivel->nome . ' com a linha de pesquisa/tema ' . $nivellinhapesquisa->linhapesquisa->nome . ' foi removida dessa seleção.');
        \UspTheme::activeUrl('selecoes');
        return redirect()->to(url('selecoes/edit/' . $selecao->id))->with($this->monta_compact($selecao, 'edit', 'niveislinhaspesquisa'));    // se fosse return view, um eventual F5 do usuário duplicaria o registro... POSTs devem ser com redirect
    }

    /**
     * Adicionar disciplinas relacionadas à seleção
     * autorizado a qualquer um que tenha acesso à seleção
     * request->codpes = required, int
     */
    public function storeDisciplina(Request $request, Selecao $selecao)
    {
        Gate::authorize('selecoes.update', $selecao);

        $request->validate([
            'id' => 'required',
        ],
        [
            'id.required' => 'Disciplina obrigatória',
        ]);

        // transaction para não ter problema de inconsistência do DB
        $db_transaction = DB::transaction(function () use ($request, $selecao) {

            $disciplina = Disciplina::where('id', $request->id)->first();

            $existia = $selecao->disciplinas()->detach($disciplina);
            $selecao->disciplinas()->attach($disciplina);
            $selecao->atualizarStatus();

            return ['disciplina' => $disciplina, 'existia' => $existia];
        });

        if (!$db_transaction['existia'])
            $request->session()->flash('alert-success', 'A disciplina ' . $db_transaction['disciplina']->sigla . ' - ' . $db_transaction['disciplina']->nome . ' foi adicionada à essa seleção.');
        else
            $request->session()->flash('alert-info', 'A disciplina ' . $db_transaction['disciplina']->sigla . ' - ' . $db_transaction['disciplina']->nome . ' já estava vinculada à essa seleção.');
        \UspTheme::activeUrl('selecoes');
        return redirect()->to(url('selecoes/edit/' . $selecao->id))->with($this->monta_compact($selecao, 'edit', 'disciplinas'));    // se fosse return view, um eventual F5 do usuário duplicaria o registro... POSTs devem ser com redirect
    }

    /**
     * Remove disciplinas relacionadas à seleção
     * $user = required
     */
    public function destroyDisciplina(Request $request, Selecao $selecao, Disciplina $disciplina)
    {
        Gate::authorize('selecoes.update', $selecao);

        // transaction para não ter problema de inconsistência do DB
        $db_transaction = DB::transaction(function () use ($selecao, $disciplina) {

            $selecao->disciplinas()->detach($disciplina);
            $selecao->atualizarStatus();
        });

        $request->session()->flash('alert-success', 'A disciplina ' . $disciplina->sigla . ' - '. $disciplina->nome . ' foi removida dessa seleção.');
        \UspTheme::activeUrl('selecoes');
        return redirect()->to(url('selecoes/edit/' . $selecao->id))->with($this->monta_compact($selecao, 'edit', 'disciplinas'));
    }

    /**
     * Adicionar motivos de isenção de taxa relacionados à seleção
     * autorizado a qualquer um que tenha acesso à seleção
     * request->codpes = required, int
     */
    public function storeMotivoIsencaoTaxa(Request $request, Selecao $selecao)
    {
        Gate::authorize('selecoes.update', $selecao);

        $request->validate([
            'id' => 'required',
        ],
        [
            'id.required' => 'Motivo de isenção de taxa obrigatório',
        ]);

        // transaction para não ter problema de inconsistência do DB
        $db_transaction = DB::transaction(function () use ($request, $selecao) {

            $motivoisencaotaxa = MotivoIsencaoTaxa::where('id', $request->id)->first();

            $existia = $selecao->motivosisencaotaxa()->detach($motivoisencaotaxa);

            $selecao->motivosisencaotaxa()->attach($motivoisencaotaxa);

            return ['motivoisencaotaxa' => $motivoisencaotaxa, 'existia' => $existia];
        });

        if (!$db_transaction['existia'])
            $request->session()->flash('alert-success', 'O motivo de isenção de taxa ' . $db_transaction['motivoisencaotaxa']->nome . ' foi adicionado à essa seleção');
        else
            $request->session()->flash('alert-info', 'O motivo de isenção de taxa ' . $db_transaction['motivoisencaotaxa']->nome . ' já estava vinculado à essa seleção');
        \UspTheme::activeUrl('selecoes');
        return redirect()->to(url('selecoes/edit/' . $selecao->id))->with($this->monta_compact($selecao, 'edit', 'motivosisencaotaxa'));    // se fosse return view, um eventual F5 do usuário duplicaria o registro... POSTs devem ser com redirect
    }

    /**
     * Remove motivos de isenção de taxa relacionados à seleção
     * $user = required
     */
    public function destroyMotivoIsencaoTaxa(Request $request, Selecao $selecao, MotivoIsencaoTaxa $motivoisencaotaxa)
    {
        Gate::authorize('selecoes.update', $selecao);

        $selecao->motivosisencaotaxa()->detach($motivoisencaotaxa);

        $request->session()->flash('alert-success', 'O motivo de isenção de taxa ' . $motivoisencaotaxa->nome . ' foi removido dessa seleção');
        \UspTheme::activeUrl('selecoes');
        return redirect()->to(url('selecoes/edit/' . $selecao->id))->with($this->monta_compact($selecao, 'edit', 'motivosisencaotaxa'));
    }

    /**
     * Adicionar tipos de arquivo para solicitações de isenção de taxa da seleção
     * autorizado a qualquer um que tenha acesso à seleção
     * request->codpes = required, int
     */
    public function storeTipoArquivoSolicitacaoIsencaoTaxa(Request $request, Selecao $selecao)
    {
        Gate::authorize('selecoes.update', $selecao);

        $request->validate([
            'id' => 'required',
        ],
        [
            'id.required' => 'Tipo de documento obrigatório',
        ]);

        // transaction para não ter problema de inconsistência do DB
        $db_transaction = DB::transaction(function () use ($request, $selecao) {

            $tipoarquivo = TipoArquivo::where('id', $request->id)->first();

            $existia = $selecao->tiposarquivo()->detach($tipoarquivo);

            $selecao->tiposarquivo()->attach($tipoarquivo);

            return ['tipoarquivo' => $tipoarquivo, 'existia' => $existia];
        });

        if (!$db_transaction['existia'])
            $request->session()->flash('alert-success', 'O tipo de documento ' . $db_transaction['tipoarquivo']->nome . ' foi adicionado à essa seleção');
        else
            $request->session()->flash('alert-info', 'O tipo de documento ' . $db_transaction['tipoarquivo']->nome . ' já estava vinculado à essa seleção');
        \UspTheme::activeUrl('selecoes');
        return redirect()->to(url('selecoes/edit/' . $selecao->id))->with($this->monta_compact($selecao, 'edit', 'tiposarquivosolicitacaoisencaotaxa'));    // se fosse return view, um eventual F5 do usuário duplicaria o registro... POSTs devem ser com redirect
    }

    /**
     * Remove tipos de arquivo para solicitações de isenção de taxa da seleção
     * $user = required
     */
    public function destroyTipoArquivoSolicitacaoIsencaoTaxa(Request $request, Selecao $selecao, TipoArquivo $tipoarquivo)
    {
        Gate::authorize('selecoes.update', $selecao);

        $selecao->tiposarquivo()->detach($tipoarquivo);

        $request->session()->flash('alert-success', 'O tipo de documento ' . $tipoarquivo->nome . ' foi removido dessa seleção');
        \UspTheme::activeUrl('selecoes');
        return redirect()->to(url('selecoes/edit/' . $selecao->id))->with($this->monta_compact($selecao, 'edit', 'tiposarquivosolicitacaoisencaotaxa'));
    }

    /**
     * Adicionar tipos de arquivo para inscrições da seleção
     * autorizado a qualquer um que tenha acesso à seleção
     * request->codpes = required, int
     */
    public function storeTipoArquivoInscricao(Request $request, Selecao $selecao)
    {
        Gate::authorize('selecoes.update', $selecao);

        $request->validate([
            'id' => 'required',
        ],
        [
            'id.required' => 'Tipo de documento obrigatório',
        ]);

        // transaction para não ter problema de inconsistência do DB
        $db_transaction = DB::transaction(function () use ($request, $selecao) {

            $tipoarquivo = TipoArquivo::where('id', $request->id)->first();

            $existia = $selecao->tiposarquivo()->detach($tipoarquivo);

            $selecao->tiposarquivo()->attach($tipoarquivo);

            return ['tipoarquivo' => $tipoarquivo, 'existia' => $existia];
        });

        if (!$db_transaction['existia'])
            $request->session()->flash('alert-success', 'O tipo de documento ' . $db_transaction['tipoarquivo']->nome . ' foi adicionado à essa seleção');
        else
            $request->session()->flash('alert-info', 'O tipo de documento ' . $db_transaction['tipoarquivo']->nome . ' já estava vinculado à essa seleção');
        \UspTheme::activeUrl('selecoes');
        return redirect()->to(url('selecoes/edit/' . $selecao->id))->with($this->monta_compact($selecao, 'edit', 'tiposarquivoinscricao'));    // se fosse return view, um eventual F5 do usuário duplicaria o registro... POSTs devem ser com redirect
    }

    /**
     * Remove tipos de arquivo para inscrições da seleção
     * $user = required
     */
    public function destroyTipoArquivoInscricao(Request $request, Selecao $selecao, TipoArquivo $tipoarquivo)
    {
        Gate::authorize('selecoes.update', $selecao);

        $selecao->tiposarquivo()->detach($tipoarquivo);

        $request->session()->flash('alert-success', 'O tipo de documento ' . $tipoarquivo->nome . ' foi removido dessa seleção');
        \UspTheme::activeUrl('selecoes');
        return redirect()->to(url('selecoes/edit/' . $selecao->id))->with($this->monta_compact($selecao, 'edit', 'tiposarquivoinscricao'));
    }

    /**
     * Adicionar tipos de arquivo para matrículas da seleção
     * autorizado a qualquer um que tenha acesso à seleção
     * request->codpes = required, int
     */
    public function storeTipoArquivoMatricula(Request $request, Selecao $selecao)
    {
        Gate::authorize('selecoes.update', $selecao);

        $request->validate([
            'id' => 'required',
        ],
        [
            'id.required' => 'Tipo de documento obrigatório',
        ]);

        // transaction para não ter problema de inconsistência do DB
        $db_transaction = DB::transaction(function () use ($request, $selecao) {

            $tipoarquivo = TipoArquivo::where('id', $request->id)->first();

            $existia = $selecao->tiposarquivo()->detach($tipoarquivo);

            $selecao->tiposarquivo()->attach($tipoarquivo);

            return ['tipoarquivo' => $tipoarquivo, 'existia' => $existia];
        });

        if (!$db_transaction['existia'])
            $request->session()->flash('alert-success', 'O tipo de documento ' . $db_transaction['tipoarquivo']->nome . ' foi adicionado à essa seleção');
        else
            $request->session()->flash('alert-info', 'O tipo de documento ' . $db_transaction['tipoarquivo']->nome . ' já estava vinculado à essa seleção');
        \UspTheme::activeUrl('selecoes');
        return redirect()->to(url('selecoes/edit/' . $selecao->id))->with($this->monta_compact($selecao, 'edit', 'tiposarquivomatricula'));    // se fosse return view, um eventual F5 do usuário duplicaria o registro... POSTs devem ser com redirect
    }

    /**
     * Remove tipos de arquivo para matrículas da seleção
     * $user = required
     */
    public function destroyTipoArquivoMatricula(Request $request, Selecao $selecao, TipoArquivo $tipoarquivo)
    {
        Gate::authorize('selecoes.update', $selecao);

        $selecao->tiposarquivo()->detach($tipoarquivo);

        $request->session()->flash('alert-success', 'O tipo de documento ' . $tipoarquivo->nome . ' foi removido dessa seleção');
        \UspTheme::activeUrl('selecoes');
        return redirect()->to(url('selecoes/edit/' . $selecao->id))->with($this->monta_compact($selecao, 'edit', 'tiposarquivomatricula'));
    }

    /**
     * Baixa as solicitações de isenção de taxa especificadas
     *
     * @param $request->ano
     * @param $selecao
     * @return Stream
     */
    public function downloadSolicitacoesIsencaoTaxa(Request $request, Selecao $selecao)
    {
        Gate::authorize('selecoes.view', $selecao);
        $request->validate([
            'ano' => 'required|integer|min:2000|max:' . (date('Y') + 1),
        ]);
        $ano = $request->ano;

        $solicitacoesisencaotaxa = SolicitacaoIsencaoTaxa::listarSolicitacoesIsencaoTaxaPorSelecao($selecao, $ano);

        // vamos pegar o template da seleção para saber quais são os campos extras
        $template = json_decode(JSONForms::orderTemplate($selecao->template_solicitacoesisencaotaxa), true);
        $keys = array_keys($template);

        $arr = [];
        foreach ($solicitacoesisencaotaxa as $solicitacaoisencaotaxa) {
            $i = [];

            $extras = json_decode($solicitacaoisencaotaxa->extras, true) ?? [];
            $i['numero_solicitacao'] = $solicitacaoisencaotaxa->id;
            if ($selecao->categoria->nome != 'Aluno Especial')
                $i['programa'] = $solicitacaoisencaotaxa->selecao->programa->nomeCompleto();
            $i['selecao'] = $solicitacaoisencaotaxa->selecao->nome;
            $i['estado'] = $solicitacaoisencaotaxa->estado;
            $i['motivo_isencao_taxa'] = MotivoIsencaoTaxa::where('id', $extras['motivo_isencao_taxa'])->first()->nome;
            $autor = $solicitacaoisencaotaxa->pessoas('Autor');
            $i['autor'] = $autor ? $autor->name : '';
            foreach ($keys as $field)
                if (in_array($field, ['nome', 'tipo_de_documento', 'numero_do_documento', 'cpf', 'e_mail']))    // somente estes campos do formulário da seleção são utilizados na solicitação de isenção de taxa
                    $i[$field] = isset($extras[$field]) ? $extras[$field] : '';
            $i['criado_em'] = $solicitacaoisencaotaxa->created_at->format('d/m/Y');
            $i['atualizado_em'] = $solicitacaoisencaotaxa->updated_at->format('d/m/Y');

            $arr[] = $i;
        }

        $writer = SimpleExcelWriter::streamDownload(ClasseUtils::obterClasseNomeAbreviada('Selecao') . $selecao->id . '_' . ClasseUtils::obterClasseNomeAbreviadaPlural('SolicitacaoIsencaoTaxa') . '_' . $ano . '.xlsx')
            ->addRows($arr);
    }

    /**
     * Baixa as inscrições especificadas
     *
     * @param $request->ano
     * @param $selecao
     * @return Stream
     */
    public function downloadInscricoes(Request $request, Selecao $selecao)
    {
        Gate::authorize('selecoes.view', $selecao);
        $request->validate([
            'ano' => 'required|integer|min:2000|max:' . (date('Y') + 1),
        ]);
        $ano = $request->ano;

        $inscricoes = Inscricao::listarInscricoesPorSelecao($selecao, $ano);

        // vamos pegar o template da seleção para saber quais são os campos extras
        $template = json_decode(JSONForms::orderTemplate($selecao->template_inscricoes), true);
        $keys = array_keys($template);

        $arr = [];
        foreach ($inscricoes as $inscricao) {
            $i = [];

            $extras = json_decode($inscricao->extras, true) ?? [];
            $i['numero_inscricao'] = $inscricao->id;
            if ($selecao->categoria->nome != 'Aluno Especial')
                $i['programa'] = $inscricao->selecao->programa->nomeCompleto();
            $i['selecao'] = $inscricao->selecao->nome;
            $i['estado'] = $inscricao->estado;
            if ($selecao->categoria->nome != 'Aluno Especial') {
                $i['nivel'] = isset($extras['nivel']) ? Nivel::where('id', $extras['nivel'])->first()->nome : '';
                $i['linha_pesquisa'] = isset($extras['linha_pesquisa']) ? LinhaPesquisa::where('id', $extras['linha_pesquisa'])->first()->nome : '';
            } else
                $i['disciplinas'] = Disciplina::whereIn('id', $extras['disciplinas'] ?? [])->pluck('sigla')->implode(', ');
            $autor = $inscricao->pessoas('Autor');
            $i['autor'] = $autor ? $autor->name : '';
            foreach ($keys as $field)
                $i[$field] = isset($extras[$field]) ? $extras[$field] : '';
            $i['criado_em'] = $inscricao->created_at->format('d/m/Y');
            $i['atualizado_em'] = $inscricao->updated_at->format('d/m/Y');

            $arr[] = $i;
        }

        $writer = SimpleExcelWriter::streamDownload(ClasseUtils::obterClasseNomeAbreviada('Selecao') . $selecao->id . '_' . ClasseUtils::obterClasseNomeAbreviadaPlural('Inscricao') . '_' . $ano . '.xlsx')
            ->addRows($arr);
    }

    /**
     * Baixa as matrículas especificadas
     *
     * @param $request->ano
     * @param $selecao
     * @return Stream
     */
    public function downloadMatriculas(Request $request, Selecao $selecao)
    {
        Gate::authorize('selecoes.view', $selecao);
        $request->validate([
            'ano' => 'required|integer|min:2000|max:' . (date('Y') + 1),
        ]);
        $ano = $request->ano;

        $matriculas = Matricula::listarMatriculasPorSelecao($selecao, $ano);

        // vamos pegar o template da seleção para saber quais são os campos extras
        $template = json_decode(JSONForms::orderTemplate($selecao->template_matriculas), true);
        $keys = array_keys($template);

        $arr = [];
        foreach ($matriculas as $matricula) {
            $i = [];

            $extras = json_decode($matricula->extras, true) ?? [];
            $i['numero_matricula'] = $matricula->id;
            if ($selecao->categoria->nome != 'Aluno Especial')
                $i['programa'] = $matricula->selecao->programa->nomeCompleto();
            $i['selecao'] = $matricula->selecao->nome;
            $i['estado'] = $matricula->estado;
            if ($selecao->categoria->nome != 'Aluno Especial') {
                $i['nivel'] = isset($extras['nivel']) ? Nivel::where('id', $extras['nivel'])->first()->nome : '';
                $i['linha_pesquisa'] = isset($extras['linha_pesquisa']) ? LinhaPesquisa::where('id', $extras['linha_pesquisa'])->first()->nome : '';
            } else
                $i['disciplinas'] = Disciplina::whereIn('id', $extras['disciplinas'] ?? [])->pluck('sigla')->implode(', ');
            $autor = $matricula->pessoas('Autor');
            $i['autor'] = $autor ? $autor->name : '';
            foreach ($keys as $field)
                $i[$field] = isset($extras[$field]) ? $extras[$field] : '';
            $i['criado_em'] = $matricula->created_at->format('d/m/Y');
            $i['atualizado_em'] = $matricula->updated_at->format('d/m/Y');

            $arr[] = $i;
        }

        $writer = SimpleExcelWriter::streamDownload(ClasseUtils::obterClasseNomeAbreviada('Selecao') . $selecao->id . '_' . ClasseUtils::obterClasseNomeAbreviadaPlural('Matricula') . '_' . $ano . '.xlsx')
            ->addRows($arr);
    }

    private function monta_compact(Selecao $selecao, string $modo, ?string $scroll = null)
    {
        $data = (object) self::$data;
        $selecao->template_solicitacoesisencaotaxa = JSONForms::orderTemplate($selecao->template_solicitacoesisencaotaxa);
        $selecao->template_inscricoes = JSONForms::orderTemplate($selecao->template_inscricoes);
        $selecao->template_matriculas = JSONForms::orderTemplate($selecao->template_matriculas);
        $objeto = $selecao;
        $classe_nome = 'Selecao';
        $classe_nome_plural = 'selecoes';
        $rules = (new SelecaoRequest())->rules();
        $objeto->niveislinhaspesquisa = NivelLinhaPesquisa::obterNiveisLinhasPesquisaDaSelecao($objeto);
        $niveislinhaspesquisa = NivelLinhaPesquisa::obterNiveisLinhasPesquisaPossiveis($selecao->programa_id);
        $disciplinas = Disciplina::obterDisciplinasPossiveis();
        $objeto->disciplinas = $objeto->disciplinas->sortBy('sigla');
        $motivosisencaotaxa = MotivoIsencaoTaxa::listarMotivosIsencaoTaxa();
        $niveis_selecao = ($selecao->categoria?->nome == 'Aluno Especial' ? new Collection() : (!empty($nivel) ? collect([['nome' => $nivel]]) : Nivel::all()));
        $objeto->tiposarquivo = TipoArquivo::obterTiposArquivoPossiveis('Selecao', null, $selecao->programa_id)
                            ->filter(function ($tipoarquivo) use ($selecao) { return ($tipoarquivo->nome !== 'Normas para Isenção de Taxa') || $selecao->tem_taxa; })
                        ->merge(TipoArquivo::obterTiposArquivoDaSelecao('SolicitacaoIsencaoTaxa', null, $selecao))
                        ->merge(TipoArquivo::obterTiposArquivoDaSelecao('Inscricao', $niveis_selecao, $selecao)
                        ->merge(TipoArquivo::obterTiposArquivoDaSelecao('Matricula', $niveis_selecao, $selecao))
                            ->filter(function ($tipoarquivo) { return !str_starts_with($tipoarquivo->nome, 'Boleto(s) de Pagamento'); }));
        $tiposarquivo_selecao = TipoArquivo::obterTiposArquivoPossiveis('Selecao', null, $selecao->programa_id);
        $tiposarquivo_solicitacaoisencaotaxa = TipoArquivo::obterTiposArquivoPossiveis('SolicitacaoIsencaoTaxa', null, $selecao->programa_id);
        $tiposarquivo_inscricao = TipoArquivo::obterTiposArquivoPossiveis('Inscricao', ($selecao->categoria?->nome == 'Aluno Especial' ? new Collection() : Nivel::all()), $selecao->programa_id);
        $tiposarquivo_matricula = TipoArquivo::obterTiposArquivoPossiveis('Matricula', ($selecao->categoria?->nome == 'Aluno Especial' ? new Collection() : Nivel::all()), $selecao->programa_id);
        $programas = Programa::all()->map(function ($programa) {
            $programa->setAttribute('fazInscricoes', $programa->fazInscricoes());
            $programa->setAttribute('fazMatriculas', $programa->fazMatriculas());
            return $programa;
        });
        $max_upload_size = config('selecoes-pos.upload_max_filesize');

        return compact('data', 'objeto', 'classe_nome', 'classe_nome_plural', 'modo', 'niveislinhaspesquisa', 'disciplinas', 'motivosisencaotaxa', 'tiposarquivo_selecao', 'tiposarquivo_solicitacaoisencaotaxa', 'tiposarquivo_inscricao', 'tiposarquivo_matricula', 'programas', 'max_upload_size', 'rules', 'scroll');
    }
}
