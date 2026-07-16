<?php

namespace App\Policies;

use App\Models\Selecao;
use App\Models\SolicitacaoIsencaoTaxa;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;

class SolicitacaoIsencaoTaxaPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view their solicitações de isenção de taxa.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function viewTheir(User $user)
    {
        return Gate::allows('perfilusuario');
    }

    /**
     * Determine whether the user can view all solicitações de isenção de taxa.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return Gate::any(['perfiladmin', 'perfilgerente']);
    }

    /**
     * Determine whether the user can view the solicitação de isenção de taxa.
     *
     * @param  \App\Models\User                    $user
     * @param  \App\Models\SolicitacaoIsencaoTaxa  $solicitacaoisencaotaxa
     * @return mixed
     */
    public function view(User $user, SolicitacaoIsencaoTaxa $solicitacaoisencaotaxa)
    {
        if (Gate::allows('perfiladmin'))
            return true;
        elseif (Gate::allows('perfilgerente'))
            return $user->gerenciaPrograma($solicitacaoisencaotaxa->selecao->programa_id);
        else
            return ($solicitacaoisencaotaxa->pessoas('Autor')->id == $user->id);    // permite que o usuário autor da solicitação de isenção de taxa a visualize
    }

    /**
     * Determine whether the user can create solicitações de isenção de taxa.
     *
     * @param  \App\User             $user
     * @param  ?\App\Models\Selecao  $selecao
     * @return mixed
     */
    public function create(User $user, ?Selecao $selecao = null)
    {
        if (!is_null($selecao)) {
            $selecao->atualizarStatus();
            if (!str_starts_with($selecao->estado, 'Período de Solicitações de Isenção de Taxa'))
                return false;
        }

        return Gate::allows('perfilusuario');
    }

    /**
     * Determine whether the user can update the solicitação de isenção de taxa.
     *
     * @param  \App\Models\User                    $user
     * @param  \App\Models\SolicitacaoIsencaoTaxa  $solicitacaoisencaotaxa
     * @return mixed
     */
    public function update(User $user, SolicitacaoIsencaoTaxa $solicitacaoisencaotaxa)
    {
        $selecao = $solicitacaoisencaotaxa->selecao;
        $selecao->atualizarStatus();
        if (!str_starts_with($selecao->estado, 'Período de Solicitações de Isenção de Taxa'))
            return false;

        return (Gate::allows('perfilusuario') && ($solicitacaoisencaotaxa->pessoas('Autor')->id == $user->id));    // permite que apenas o usuário autor da solicitação de isenção de taxa a edite
    }

    /**
     * Determine whether the user can update the solicitação de isenção de taxa status.
     *
     * @param  \App\Models\User                    $user
     * @param  \App\Models\SolicitacaoIsencaoTaxa  $solicitacaoisencaotaxa
     * @return mixed
     */
    public function updateStatus(User $user, SolicitacaoIsencaoTaxa $solicitacaoisencaotaxa)
    {
        if (Gate::allows('perfiladmin'))
            return true;
        elseif (Gate::allows('perfilgerente'))
            return $user->gerenciaPrograma($solicitacaoisencaotaxa->selecao->programa_id);
        else
            return false;
    }

    /**
     * Determine whether the user can update the solicitação de isenção de taxa arquivos.
     *
     * @param  \App\Models\User                    $user
     * @param  \App\Models\SolicitacaoIsencaoTaxa  $solicitacaoisencaotaxa
     * @return mixed
     */
    public function updateArquivos(User $user, SolicitacaoIsencaoTaxa $solicitacaoisencaotaxa)
    {
        if (Gate::allows('perfiladmin'))
            return true;
        elseif (Gate::allows('perfilgerente'))
            return $user->gerenciaPrograma($solicitacaoisencaotaxa->selecao->programa_id);
        elseif (Gate::allows('perfilusuario'))
            return ($solicitacaoisencaotaxa->pessoas('Autor')->id == $user->id);
    }
}
