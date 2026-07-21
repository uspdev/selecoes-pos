<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ArquivoController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\DisciplinaController;
use App\Http\Controllers\EnderecoController;
use App\Http\Controllers\FuncaoController;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\InscricaoController;
use App\Http\Controllers\LimpezaDadosController;
use App\Http\Controllers\LinhaPesquisaController;
use App\Http\Controllers\LocalUserController;
use App\Http\Controllers\MatriculaController;
use App\Http\Controllers\MotivoIsencaoTaxaController;
use App\Http\Controllers\OrientadorController;
use App\Http\Controllers\ParametroController;
use App\Http\Controllers\ProgramaController;
use App\Http\Controllers\ResponsavelController;
use App\Http\Controllers\SelecaoController;
use App\Http\Controllers\SolicitacaoIsencaoTaxaController;
use App\Http\Controllers\TipoArquivoController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Auth\LoginController;

Route::get('/', [IndexController::class, 'index'])->name('home');

// SENHA ÚNICA
Route::get('login', [LoginController::class, 'redirectToProvider'])->name('login');
Route::get('callback', [LoginController::class, 'handleProviderCallback']);
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

// LOCAL USERS > LOGIN
Route::get('localusers/login', [LocalUserController::class, 'showLogin'])->name('localusers.showlogin');
Route::post('localusers/login', [LocalUserController::class, 'login'])->name('localusers.login');
Route::post('localusers/esqueceusenha', [LocalUserController::class, 'esqueceuSenha'])->name('localusers.esqueceusenha');
Route::get('localusers/redefinesenha/{token}', [LocalUserController::class, 'iniciaRedefinicaoSenha'])->name('localusers.iniciaredefinicaosenha');
Route::post('localusers/redefinesenha', [LocalUserController::class, 'redefineSenha'])->name('localusers.redefinesenha');
Route::get('localusers/confirmaemail/{token}', [LocalUserController::class, 'confirmaEmail'])->name('localusers.confirmaemail');
Route::put('localusers/adminconfirmaemail/{localuser}', [LocalUserController::class, 'adminConfirmaEmail'])->name('localusers.adminconfirmaemail');
Route::post('localusers/reenviaemailconfirmacao', [LocalUserController::class, 'reenviaEmailConfirmacao'])->name('localusers.reenviaemailconfirmacao');

// SOLICITAÇÕES DE ISENÇÃO DE TAXA
Route::get('solicitacoesisencaotaxa', [SolicitacaoIsencaoTaxaController::class, 'index'])->name('solicitacoesisencaotaxa.index');
Route::get('solicitacoesisencaotaxa/create', [SolicitacaoIsencaoTaxaController::class, 'listaSelecoesParaSolicitacaoIsencaoTaxa'])->name('solicitacoesisencaotaxa.create');
Route::get('solicitacoesisencaotaxa/create/{selecao}', [SolicitacaoIsencaoTaxaController::class, 'create'])->name('solicitacoesisencaotaxa.create.selecao');
Route::post('solicitacoesisencaotaxa/create', [SolicitacaoIsencaoTaxaController::class, 'store'])->name('solicitacoesisencaotaxa.store');
Route::get('solicitacoesisencaotaxa/edit/{solicitacaoisencaotaxa}', [SolicitacaoIsencaoTaxaController::class, 'edit'])->name('solicitacoesisencaotaxa.edit');
Route::put('solicitacoesisencaotaxa/edit/{solicitacaoisencaotaxa}', [SolicitacaoIsencaoTaxaController::class, 'update'])->name('solicitacoesisencaotaxa.update');

// INSCRIÇÕES
Route::get('inscricoes', [InscricaoController::class, 'index'])->name('inscricoes.index');
Route::get('inscricoes/create', [InscricaoController::class, 'listaSelecoesParaNovaInscricao'])->name('inscricoes.create');
Route::get('inscricoes/create/{selecao}/{nivel}', [InscricaoController::class, 'create']);    // define a rota mais específica antes da mais geral (na linha de baixo)
Route::get('inscricoes/create/{selecao}', [InscricaoController::class, 'create']);
Route::post('inscricoes/create', [InscricaoController::class, 'store'])->name('inscricoes.store');
Route::get('inscricoes/edit/{inscricao}', [InscricaoController::class, 'edit'])->name('inscricoes.edit');
Route::put('inscricoes/edit/{inscricao}', [InscricaoController::class, 'update'])->name('inscricoes.update');
Route::post('inscricoes/geraboletos/{inscricao}', [InscricaoController::class, 'geraBoletos'])->name('inscricoes.geraboletos');
Route::post('inscricoes/{inscricao}/enviaboleto/{arquivo}', [InscricaoController::class, 'enviaBoleto'])->name('inscricoes.enviaboleto');

// INSCRIÇÕES > DISCIPLINAS
Route::post('inscricoes/{inscricao}/disciplinas', [InscricaoController::class, 'storeDisciplina']);
Route::delete('inscricoes/{inscricao}/disciplinas/{disciplina}', [InscricaoController::class, 'destroyDisciplina']);

// MATRÍCULAS
Route::get('matriculas', [MatriculaController::class, 'index'])->name('matriculas.index');
Route::get('matriculas/create', [MatriculaController::class, 'listaSelecoesParaNovaMatricula'])->name('matriculas.create');
Route::get('matriculas/create/{selecao}/{nivel}', [MatriculaController::class, 'create']);    // define a rota mais específica antes da mais geral (na linha de baixo)
Route::get('matriculas/create/{selecao}', [MatriculaController::class, 'create']);
Route::post('matriculas/create', [MatriculaController::class, 'store'])->name('matriculas.store');
Route::get('matriculas/edit/{matricula}', [MatriculaController::class, 'edit'])->name('matriculas.edit');
Route::put('matriculas/edit/{matricula}', [MatriculaController::class, 'update'])->name('matriculas.update');
Route::post('matriculas/geraboletos/{matricula}', [MatriculaController::class, 'geraBoletos'])->name('matriculas.geraboletos');
Route::post('matriculas/{matricula}/enviaboleto/{arquivo}', [MatriculaController::class, 'enviaBoleto'])->name('matriculas.enviaboleto');

// MATRÍCULAS > DISCIPLINAS
Route::post('matriculas/{matricula}/disciplinas', [MatriculaController::class, 'storeDisciplina']);
Route::delete('matriculas/{matricula}/disciplinas/{disciplina}', [MatriculaController::class, 'destroyDisciplina']);

// CONSULTA DE CEP
Route::get('consulta-cep', [EnderecoController::class, 'consultarCep'])->name('consulta.cep');

// ARQUIVOS
Route::get('arquivos/ziptodosdoobjeto/{classe_nome}/{objeto_id}', [ArquivoController::class, 'zipTodosDoObjeto'])->name('arquivos.ziptodosdoobjeto');    // pelo fato do objeto poder ser de diferentes tipos, é melhor usarmos o id dele ao invés dele propriamente dito
Route::get('arquivos/downloadtodosdoobjeto/{classe_nome}/{objeto_id}', [ArquivoController::class, 'downloadTodosDoObjeto'])->name('arquivos.downloadtodosdoobjeto');
Route::get('arquivos/ziptodosdosobjetosdaselecao/{classe_nome}/{selecao}', [ArquivoController::class, 'zipTodosDosObjetosDaSelecao'])->name('arquivos.ziptodosdosobjetosdaselecao');
Route::get('arquivos/downloadtodosdosobjetosdaselecao/{classe_nome}/{selecao}', [ArquivoController::class, 'downloadTodosDosObjetosDaSelecao'])->name('arquivos.downloadtodosdosobjetosdaselecao');
Route::resource('arquivos', ArquivoController::class);

// SELEÇÕES
Route::get('selecoes', [SelecaoController::class, 'index'])->name('selecoes.index');
Route::get('selecoes/create', [SelecaoController::class, 'create'])->name('selecoes.create');
Route::post('selecoes/create', [SelecaoController::class, 'store'])->name('selecoes.store');
Route::get('selecoes/edit/{selecao}', [SelecaoController::class, 'edit'])->name('selecoes.edit');
Route::put('selecoes/edit/{selecao}', [SelecaoController::class, 'update'])->name('selecoes.update');
Route::put('selecoes/edit-status/{selecao}', [SelecaoController::class, 'updateStatus'])->name('selecoes.update-status');
Route::get('selecoes/{selecao}/downloadsolicitacoesisencaotaxa', [SelecaoController::class, 'downloadSolicitacoesIsencaoTaxa'])->name('selecoes.downloadsolicitacoesisencaotaxa');
Route::get('selecoes/{selecao}/downloadinscricoes', [SelecaoController::class, 'downloadInscricoes'])->name('selecoes.downloadinscricoes');
Route::get('selecoes/{selecao}/downloadmatriculas', [SelecaoController::class, 'downloadMatriculas'])->name('selecoes.downloadmatriculas');

// SELEÇÕES > NÍVEIS + LINHAS DE PESQUISA/TEMAS
Route::post('selecoes/{selecao}/niveislinhaspesquisa', [SelecaoController::class, 'storeNiveisLinhasPesquisa']);
Route::delete('selecoes/{selecao}/niveislinhaspesquisa/{nivellinhapesquisa}', [SelecaoController::class, 'destroyNivelLinhaPesquisa']);

// SELEÇÕES > DISCIPLINAS
Route::post('selecoes/{selecao}/disciplinas', [SelecaoController::class, 'storeDisciplina']);
Route::delete('selecoes/{selecao}/disciplinas/{disciplina}', [SelecaoController::class, 'destroyDisciplina']);

// SELEÇÕES > MOTIVOS DE ISENÇÂO DE TAXA
Route::post('selecoes/{selecao}/motivosisencaotaxa', [SelecaoController::class, 'storeMotivoIsencaoTaxa']);
Route::delete('selecoes/{selecao}/motivosisencaotaxa/{motivoisencaotaxa}', [SelecaoController::class, 'destroyMotivoIsencaoTaxa']);

// SELEÇÕES > ORIENTADORES
Route::post('selecoes/{selecao}/orientadores', [SelecaoController::class, 'storeOrientador']);
Route::delete('selecoes/{selecao}/orientadores/{orientador}', [SelecaoController::class, 'destroyOrientador']);

// SELEÇÕES > TIPOS DE ARQUIVO
Route::post('selecoes/{selecao}/tiposarquivosolicitacaoisencaotaxa', [SelecaoController::class, 'storeTipoArquivoSolicitacaoIsencaoTaxa']);
Route::delete('selecoes/{selecao}/tiposarquivosolicitacaoisencaotaxa/{tipoarquivo}', [SelecaoController::class, 'destroyTipoArquivoSolicitacaoIsencaoTaxa']);
Route::post('selecoes/{selecao}/tiposarquivoinscricao', [SelecaoController::class, 'storeTipoArquivoInscricao']);
Route::delete('selecoes/{selecao}/tiposarquivoinscricao/{tipoarquivo}', [SelecaoController::class, 'destroyTipoArquivoInscricao']);
Route::post('selecoes/{selecao}/tiposarquivomatricula', [SelecaoController::class, 'storeTipoArquivoMatricula']);
Route::delete('selecoes/{selecao}/tiposarquivomatricula/{tipoarquivo}', [SelecaoController::class, 'destroyTipoArquivoMatricula']);

// SELEÇÕES > FORMULÁRIOS
Route::post('selecoes/{selecao}/{classe_nome}/template_json', [SelecaoController::class, 'storeTemplateJson']);
Route::get('selecoes/{selecao}/{classe_nome}/template', [SelecaoController::class, 'createTemplate'])->name('selecoes.createtemplate');
Route::post('selecoes/{selecao}/{classe_nome}/template', [SelecaoController::class, 'storeTemplate'])->name('selecoes.storetemplate');
Route::get('selecoes/{selecao}/{classe_nome}/templatevalue/{campo}', [SelecaoController::class, 'createTemplateValue'])->name('selecoes.createtemplatevalue')->where('campo', '[a-zA-Z0-9_]+');
Route::post('selecoes/{selecao}/{classe_nome}/templatevalue/{campo}', [SelecaoController::class, 'storeTemplateValue'])->name('selecoes.storetemplatevalue')->where('campo', '[a-zA-Z0-9_]+');

// CATEGORIAS
Route::resource('categorias', CategoriaController::class);

// PROGRAMAS
Route::resource('programas', ProgramaController::class);

// LINHAS DE PESQUISA/TEMAS
Route::resource('linhaspesquisa', LinhaPesquisaController::class);
Route::post('linhaspesquisa/create', [LinhaPesquisaController::class, 'store']);
Route::get('linhaspesquisa/edit/{linhapesquisa}', [LinhaPesquisaController::class, 'edit']);
Route::put('linhaspesquisa/edit/{linhapesquisa}', [LinhaPesquisaController::class, 'update']);

// LINHAS DE PESQUISA/TEMAS > NÍVEIS
Route::post('linhaspesquisa/{linhapesquisa}/niveis', [LinhaPesquisaController::class, 'storeNivel']);
Route::delete('linhaspesquisa/{linhapesquisa}/niveis/{nivel}', [LinhaPesquisaController::class, 'destroyNivel']);

// LINHAS DE PESQUISA/TEMAS > ORIENTADORES
Route::post('linhaspesquisa/{linhapesquisa}/orientadores', [LinhaPesquisaController::class, 'storeOrientador']);
Route::delete('linhaspesquisa/{linhapesquisa}/orientadores/{orientador}', [LinhaPesquisaController::class, 'destroyOrientador']);

// DISCIPLINAS
Route::resource('disciplinas', DisciplinaController::class);

// MOTIVOS DE ISENÇÃO DE TAXA
Route::resource('motivosisencaotaxa', MotivoIsencaoTaxaController::class);

// ORIENTADORES
Route::resource('orientadores', OrientadorController::class);

// TIPOS DE ARQUIVO
Route::resource('tiposarquivo', TipoArquivoController::class);
Route::post('tiposarquivo/create', [TipoArquivoController::class, 'store']);
Route::get('tiposarquivo/edit/{tipoarquivo}', [TipoArquivoController::class, 'edit']);
Route::put('tiposarquivo/edit/{tipoarquivo}', [TipoArquivoController::class, 'update']);

// TIPOS DE ARQUIVO > CATEGORIAS
Route::post('tiposarquivo/{tipoarquivo}/categorias', [TipoArquivoController::class, 'storeCategoria']);
Route::delete('tiposarquivo/{tipoarquivo}/categorias/{categoria}', [TipoArquivoController::class, 'destroyCategoria']);

// TIPOS DE ARQUIVO > NÍVEIS + PROGRAMAS
Route::post('tiposarquivo/{tipoarquivo}/niveisprogramas', [TipoArquivoController::class, 'storeNivelPrograma']);
Route::delete('tiposarquivo/{tipoarquivo}/niveisprogramas/{nivelprograma}', [TipoArquivoController::class, 'destroyNivelPrograma']);

// PARÂMETROS
Route::get('parametros', [ParametroController::class, 'edit'])->name('parametros.edit');
Route::put('parametros', [ParametroController::class, 'update'])->name('parametros.update');

// FUNÇÕES
Route::get('funcoes', [FuncaoController::class, 'edit'])->name('funcoes.edit');
Route::put('funcoes', [FuncaoController::class, 'update'])->name('funcoes.update');

// RESPONSÁVEIS
Route::get('responsaveis/{id}/{funcao}/{programa_id?}', [ResponsavelController::class, 'show']);

// LOCAL USERS
Route::resource('localusers', LocalUserController::class);

// USERS
Route::get('search/partenome', [UserController::class, 'partenome']);
Route::get('search/codpes', [UserController::class, 'codpes']);
Route::get('users/perfil/{perfil}', [UserController::class, 'trocarPerfil']);
Route::get('users/meuperfil', [UserController::class, 'meuperfil']);
Route::resource('users', UserController::class);

// LIMPEZA DE DADOS
Route::get('limpezadados', [LimpezaDadosController::class, 'showForm'])->name('limpezadados.showForm');
Route::post('limpezadados', [LimpezaDadosController::class, 'run'])->name('limpezadados.run');

// ADMIN
Route::get('admin', [AdminController::class, 'index']);
Route::get('admin/get_oauth_file/{filename}', [AdminController::class, 'getOauthFile']);
