<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::define('admin', function ($user) {
            return $user->is_admin;
        });

        Gate::define('gerente', function ($user) {
            return $user->is_admin;
        });

        Gate::define('docente', function ($user)  {
            return $user->is_admin;
        });

        Gate::define('usuario', function ($user) {
            return $user;
        });

        # perfis
        # o perfil é o modo como o usuário se apresenta
        # ideal para mostrar os menus e a lista de categorias
        Gate::define('perfiladmin', function (?User $user) {
            return ($user && (session('perfil') == 'admin'));
        });

        Gate::define('perfilgerente', function (?User $user) {
            return ($user && (session('perfil') == 'gerente'));
        });

        Gate::define('perfildocente', function (?User $user) {
            return ($user && (session('perfil') == 'docente'));
        });

        Gate::define('perfilusuario', function ($user) {
            return ((session('perfil') == 'usuario') || empty(session('perfil')));
        });

        Gate::define('trocarPerfil', function ($user) {
            return Gate::any(['admin', 'gerente']);
        });

        # se o admin assumir identidade de outro usuário, permite retornar
        Gate::define('desassumir', function ($user) {
            return session('adminCodpes');
        });

        # policies
        Gate::resource('admin', 'App\Policies\AdminPolicy');
        Gate::resource('arquivos', 'App\Policies\ArquivoPolicy');
        Gate::resource('categorias', 'App\Policies\CategoriaPolicy');
        Gate::resource('disciplinas', 'App\Policies\DisciplinaPolicy');
        Gate::resource('funcoes', 'App\Policies\FuncaoPolicy');
        Gate::resource('inscricoes', 'App\Policies\InscricaoPolicy');
        Gate::define('inscricoes.viewTheir', 'App\Policies\InscricaoPolicy@viewTheir');    // Gate::resource só define policies padrão (viewAny, view, create, etc.)... portanto, para policies fora do padrão (como viewTheir), precisamos explicitamente criar os apontamentos para elas
        Gate::define('inscricoes.updateStatus', 'App\Policies\InscricaoPolicy@updateStatus');
        Gate::define('inscricoes.updateArquivos', 'App\Policies\InscricaoPolicy@updateArquivos');
        Gate::define('inscricoes.geraBoletos', 'App\Policies\InscricaoPolicy@geraBoletos');
        Gate::define('inscricoes.enviaBoleto', 'App\Policies\InscricaoPolicy@enviaBoleto');
        Gate::define('limpezadados.showForm', 'App\Policies\LimpezaDadosPolicy@showForm');
        Gate::define('limpezadados.run', 'App\Policies\LimpezaDadosPolicy@run');
        Gate::resource('linhaspesquisa', 'App\Policies\LinhaPesquisaPolicy');
        Gate::resource('localusers', 'App\Policies\LocalUserPolicy');
        Gate::define('localusers.adminConfirmEmail', 'App\Policies\LocalUserPolicy@adminConfirmEmail');
        Gate::resource('matriculas', 'App\Policies\MatriculaPolicy');
        Gate::define('matriculas.viewTheir', 'App\Policies\MatriculaPolicy@viewTheir');    // Gate::resource só define policies padrão (viewAny, view, create, etc.)... portanto, para policies fora do padrão (como viewTheir), precisamos explicitamente criar os apontamentos para elas
        Gate::define('matriculas.updateStatus', 'App\Policies\MatriculaPolicy@updateStatus');
        Gate::define('matriculas.updateArquivos', 'App\Policies\MatriculaPolicy@updateArquivos');
        Gate::define('matriculas.geraBoletos', 'App\Policies\MatriculaPolicy@geraBoletos');
        Gate::define('matriculas.enviaBoleto', 'App\Policies\MatriculaPolicy@enviaBoleto');
        Gate::resource('motivosisencaotaxa', 'App\Policies\MotivoIsencaoTaxaPolicy');
        Gate::resource('parametros', 'App\Policies\ParametroPolicy');
        Gate::resource('programas', 'App\Policies\ProgramaPolicy');
        Gate::resource('selecoes', 'App\Policies\SelecaoPolicy');
        Gate::define('selecoes.updateArquivos', 'App\Policies\SelecaoPolicy@updateArquivos');
        Gate::resource('solicitacoesisencaotaxa', 'App\Policies\SolicitacaoIsencaoTaxaPolicy');
        Gate::define('solicitacoesisencaotaxa.viewTheir', 'App\Policies\SolicitacaoIsencaoTaxaPolicy@viewTheir');
        Gate::define('solicitacoesisencaotaxa.updateStatus', 'App\Policies\SolicitacaoIsencaoTaxaPolicy@updateStatus');
        Gate::define('solicitacoesisencaotaxa.updateArquivos', 'App\Policies\SolicitacaoIsencaoTaxaPolicy@updateArquivos');
        Gate::resource('tiposarquivo', 'App\Policies\TipoArquivoPolicy');
        Gate::resource('users', 'App\Policies\UserPolicy');
    }
}
