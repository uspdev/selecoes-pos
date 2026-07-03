<?php

namespace App\Policies;

use App\Models\Arquivo;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;

class ArquivoPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User                                                         $user
     * @param  \App\Models\Selecao ou SolicitacaoIsencaoTaxa ou Inscricao ou Matricula  $objeto
     * @param  string                                                                   $classe_nome
     * @return mixed
     */
    public function viewAny(User $user, object $objeto, string $classe_nome)
    {
        if ($classe_nome == 'Selecao')
            return true;                                           // permite que todos baixem arquivos de seleções

        if (Gate::allows('perfiladmin'))
            return true;                                           // permite que admins baixem todos os arquivos
        elseif (Gate::allows('perfilgerente')) {
            if ($user->gerenciaPrograma($objeto->selecao->programa_id))
                return true;
        } elseif (Gate::allows('perfildocente')) {
            if ($user->gerenciaProgramaFuncao('Docentes do Programa', $objeto->selecao->programa_id))
                return true;
        } elseif (Gate::allows('perfilusuario')) {
            $autor_objeto = $objeto->pessoas('Autor');
            if ($autor_objeto && ($autor_objeto->id == $user->id))
                return true;                                       // permite que usuários baixem arquivos de seus objetos
        }
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\?User    $user
     * @param  \App\Models\Arquivo  $arquivo
     * @param  string               $classe_nome
     * @return mixed
     */
    public function view(?User $user, Arquivo $arquivo, string $classe_nome)    // se não colocarmos a interrogação, esta policy não é invocada no caso de usuário não logado
    {
        if ($classe_nome == 'Selecao')
            return true;                                           // permite que todos baixem arquivos de seleções

        if (Gate::allows('perfiladmin'))
            return true;                                           // permite que admins baixem todos os arquivos
        elseif (Gate::allows('perfilgerente')) {
            foreach ($arquivo->solicitacoesisencaotaxa as $solicitacaoisencaotaxa)
                if ($user->gerenciaPrograma($solicitacaoisencaotaxa->selecao->programa_id))
                    return true;

            foreach ($arquivo->inscricoes as $inscricao)
                if ($user->gerenciaPrograma($inscricao->selecao->programa_id))
                    return true;

            foreach ($arquivo->matriculas as $matricula)
                if ($user->gerenciaPrograma($matricula->selecao->programa_id))
                    return true;
        } elseif (Gate::allows('perfildocente')) {
            foreach ($arquivo->solicitacoesisencaotaxa as $solicitacaoisencaotaxa)
                if ($user->gerenciaProgramaFuncao('Docentes do Programa', $solicitacaoisencaotaxa->selecao->programa_id))
                    return true;

            foreach ($arquivo->inscricoes as $inscricao)
                if ($user->gerenciaProgramaFuncao('Docentes do Programa', $inscricao->selecao->programa_id))
                    return true;

            foreach ($arquivo->matriculas as $matricula)
                if ($user->gerenciaProgramaFuncao('Docentes do Programa', $matricula->selecao->programa_id))
                    return true;
        } elseif (Gate::allows('perfilusuario')) {
            foreach ($arquivo->solicitacoesisencaotaxa as $solicitacaoisencaotaxa) {
                $autor_solicitacaoisencaotaxa = $solicitacaoisencaotaxa->pessoas('Autor');
                if ($autor_solicitacaoisencaotaxa && ($autor_solicitacaoisencaotaxa->id == $user->id))
                    return true;                                   // permite que usuários baixem arquivos de suas solicitações de isenção de taxa
            }

            foreach ($arquivo->inscricoes as $inscricao) {
                $autor_inscricao = $inscricao->pessoas('Autor');
                if ($autor_inscricao && ($autor_inscricao->id == $user->id))
                    return true;                                   // permite que usuários baixem arquivos de suas inscrições
            }

            foreach ($arquivo->matriculas as $matricula) {
                $autor_matricula = $matricula->pessoas('Autor');
                if ($autor_matricula && ($autor_matricula->id == $user->id))
                    return true;                                   // permite que usuários baixem arquivos de suas matrículas
            }
        }
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User                                                         $user
     * @param  \App\Models\Selecao ou SolicitacaoIsencaoTaxa ou Inscricao ou Matricula  $objeto
     * @param  string                                                                   $classe_nome
     * @return mixed
     */
    public function create(User $user, object $objeto, string $classe_nome)
    {
        if ($classe_nome == 'Selecao')
            return Gate::any(['perfiladmin', 'perfilgerente']);    // permite que admins e gerentes subam arquivos de seleção

        if (Gate::allows('perfiladmin'))
            return true;
        elseif (Gate::allows('perfilgerente'))
            return $user->gerenciaPrograma($objeto->selecao->programa_id);
        elseif (Gate::allows('perfildocente'))
            return false;
        elseif (Gate::allows('perfilusuario')) {
            $selecao = $objeto->selecao;
            $selecao->atualizarStatus();
            if ((($classe_nome == 'SolicitacaoIsencaoTaxa') && !in_array($selecao->estado, ['Período de Solicitações de Isenção de Taxa e de Inscrições/Matrículas', 'Período de Solicitações de Isenção de Taxa'])) ||
                (($classe_nome == 'Inscricao'             ) && !in_array($selecao->estado, ['Período de Solicitações de Isenção de Taxa e de Inscrições/Matrículas', 'Período de Inscrições/Matrículas'          ])) ||
                (($classe_nome == 'Matricula'             ) && !in_array($selecao->estado, ['Período de Solicitações de Isenção de Taxa e de Inscrições/Matrículas', 'Período de Inscrições/Matrículas'          ])))
                return false;

            $autor_inscricao = $objeto->pessoas('Autor');
            if ($autor_inscricao && ($autor_inscricao->id == $user->id))
                return true;                                       // permite que usuários subam arquivos em suas solicitações de isenção de taxa e inscrições

            $autor_matricula = $objeto->pessoas('Autor');
            if ($autor_matricula && ($autor_matricula->id == $user->id))
                return true;                                       // permite que usuários subam arquivos em suas matrículas
        }
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User                                                         $user
     * @param  \App\Models\Arquivo                                                      $arquivo
     * @param  \App\Models\Selecao ou SolicitacaoIsencaoTaxa ou Inscricao ou Matricula  $objeto
     * @param  string                                                                   $classe_nome
     * @return mixed
     */
    public function update(User $user, Arquivo $arquivo, object $objeto, string $classe_nome)
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User                                                         $user
     * @param  \App\Models\Arquivo                                                      $arquivo
     * @param  \App\Models\Selecao ou SolicitacaoIsencaoTaxa ou Inscricao ou Matricula  $objeto
     * @param  string                                                                   $classe_nome
     * @return mixed
     */
    public function delete(User $user, Arquivo $arquivo, object $objeto, string $classe_nome)
    {
        if ($classe_nome == 'Selecao')
            return Gate::any(['perfiladmin', 'perfilgerente']);    // permite que admins e gerentes renomeiem/apaguem arquivos de seleção

        if (Gate::allows('perfiladmin'))
            return true;
        elseif (Gate::allows('perfilgerente'))
            return $user->gerenciaPrograma($objeto->selecao->programa_id);
        elseif (Gate::allows('perfildocente'))
            return false;
        elseif (Gate::allows('perfilusuario')) {
            $selecao = $objeto->selecao;
            $selecao->atualizarStatus();
            if ((($classe_nome == 'SolicitacaoIsencaoTaxa') && !in_array($selecao->estado, ['Período de Solicitações de Isenção de Taxa e de Inscrições/Matrículas', 'Período de Solicitações de Isenção de Taxa'])) ||
                (($classe_nome == 'Inscricao'             ) && !in_array($selecao->estado, ['Período de Solicitações de Isenção de Taxa e de Inscrições/Matrículas', 'Período de Inscrições/Matrículas'          ])) ||
                (($classe_nome == 'Matricula'             ) && !in_array($selecao->estado, ['Período de Solicitações de Isenção de Taxa e de Inscrições/Matrículas', 'Período de Inscrições/Matrículas'          ])))
                return false;

            $autor_arquivo_id = $arquivo->user_id;

            $autor_solicitacaoisencaotaxa = $objeto->pessoas('Autor');
            if ($autor_solicitacaoisencaotaxa && ($autor_solicitacaoisencaotaxa->id == $user->id))
                return true;                                       // permite que usuários renomeiem/apaguem arquivos em suas solicitações de isenção de taxa

            $autor_inscricao = $objeto->pessoas('Autor');
            if (($autor_arquivo_id == $user->id) && $autor_inscricao && ($autor_inscricao->id == $user->id))
                return true;                                       // permite que usuários renomeiem/apaguem arquivos em suas inscrições

            $autor_matricula = $objeto->pessoas('Autor');
            if (($autor_arquivo_id == $user->id) && $autor_matricula && ($autor_matricula->id == $user->id))
                return true;                                       // permite que usuários renomeiem/apaguem arquivos em suas matrículas
        }
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function restore(User $user)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function forceDelete(User $user)
    {
        //
    }
}
