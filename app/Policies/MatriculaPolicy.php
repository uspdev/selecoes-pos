<?php

namespace App\Policies;

use App\Models\Arquivo;
use App\Models\Matricula;
use App\Models\Selecao;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;

class MatriculaPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view their matrículas.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function viewTheir(User $user)
    {
        return Gate::allows('perfilusuario');
    }

    /**
     * Determine whether the user can see the Matrículas menu item.
     */
    public function viewAny(User $user)
    {
        if (Gate::allows('perfiladmin'))
            return true;
        elseif (Gate::any(['perfilgerente', 'perfildocente'])) {
            if ($user->gerenciaProgramaFuncao('Serviço de Pós-Graduação') || $user->gerenciaProgramaFuncao('Coordenadores da Pós-Graduação'))
                return true;
            $programas = $this->obterProgramasParaMenu($user);
            return $programas->contains(fn($programa) => $programa->fazMatriculas());
        } else
            return false;
    }

    private function obterProgramasParaMenu(User $user)
    {
        if (Gate::allows('perfilgerente'))
            return $user->listarProgramasGerenciados();
        if (Gate::allows('perfildocente'))
            return $user->listarProgramasGerenciadosFuncao('Docentes do Programa');
    }

    /**
     * Determine whether the user can view the matrícula.
     *
     * @param  \App\Models\User       $user
     * @param  \App\Models\Matricula  $matricula
     * @return mixed
     */
    public function view(User $user, Matricula $matricula)
    {
        if (Gate::allows('perfiladmin'))
            return true;
        elseif (Gate::allows('perfilgerente'))
            return $user->gerenciaPrograma($matricula->selecao->programa_id);
        elseif (Gate::allows('perfildocente'))
            return $user->gerenciaProgramaFuncao('Docentes do Programa', $matricula->selecao->programa_id);
        else
            return ($matricula->pessoas('Autor')->id == $user->id);    // permite que o usuário autor da matrícula a visualize
    }

    /**
     * Determine whether the user can create matrículas.
     *
     * @param  \App\User             $user
     * @param  ?\App\Models\Selecao  $selecao
     * @return mixed
     */
    public function create(User $user, ?Selecao $selecao = null)
    {
        if (!is_null($selecao)) {
            $selecao->atualizarStatus();
            if (!in_array($selecao->estado, ['Período de Solicitações de Isenção de Taxa e de Inscrições/Matrículas', 'Período de Inscrições/Matrículas']))
                return false;
        }

        return Gate::allows('perfilusuario');
    }

    /**
     * Determine whether the user can update the matrícula.
     *
     * @param  \App\Models\User       $user
     * @param  \App\Models\Matricula  $matricula
     * @return mixed
     */
    public function update(User $user, Matricula $matricula)
    {
        $selecao = $matricula->selecao;
        $selecao->atualizarStatus();
        if (!in_array($selecao->estado, ['Período de Solicitações de Isenção de Taxa e de Inscrições/Matrículas', 'Período de Inscrições/Matrículas']))
            return false;

        return (Gate::allows('perfilusuario') && ($matricula->pessoas('Autor')->id == $user->id));    // permite que apenas o usuário autor da matrícula a edite
    }

    /**
     * Determine whether the user can update the matrícula status.
     *
     * @param  \App\Models\User       $user
     * @param  \App\Models\Matricula  $matricula
     * @return mixed
     */
    public function updateStatus(User $user, Matricula $matricula)
    {
        if (Gate::allows('perfiladmin'))
            return true;
        elseif (Gate::allows('perfilgerente'))
            return $user->gerenciaPrograma($matricula->selecao->programa_id);
        elseif (Gate::allows('perfildocente'))
            return false;
        else
            return false;
    }

    /**
     * Determine whether the user can update the matrícula arquivos.
     *
     * @param  \App\Models\User       $user
     * @param  \App\Models\Matricula  $matricula
     * @return mixed
     */
    public function updateArquivos(User $user, Matricula $matricula)
    {
        if (Gate::allows('perfiladmin'))
            return true;
        elseif (Gate::allows('perfilgerente'))
            return $user->gerenciaPrograma($matricula->selecao->programa_id);
        elseif (Gate::allows('perfildocente'))
            return false;
        elseif (Gate::allows('perfilusuario'))
            return ($matricula->pessoas('Autor')->id == $user->id);
    }

    /**
     * Determine whether the user can gerar boleto(s).
     *
     * @param  \App\Models\User       $user
     * @param  \App\Models\Matricula  $matricula
     * @return mixed
     */
    public function geraBoletos(User $user, Matricula $matricula)
    {
        if (Gate::allows('perfiladmin'))
            return true;
        elseif (Gate::allows('perfilgerente'))
            return $user->gerenciaPrograma($matricula->selecao->programa_id);
        elseif (Gate::allows('perfildocente'))
            return false;
        else
            return false;
    }

    /**
     * Determine whether the user can enviar um boleto.
     *
     * @param  \App\Models\User       $user
     * @param  \App\Models\Matricula  $matricula
     * @param  \App\Models\Arquivo    $arquivo
     * @return mixed
     */
    public function enviaBoleto(User $user, Matricula $matricula, Arquivo $arquivo)
    {
        if (Gate::allows('perfiladmin'))
            return true;
        elseif (Gate::allows('perfilgerente'))
            return $user->gerenciaPrograma($matricula->selecao->programa_id);
        elseif (Gate::allows('perfildocente'))
            return false;
        else
            return false;
    }
}
