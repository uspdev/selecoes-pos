<?php

namespace App\Observers;

use App\Models\Orientador;
use Uspdev\Replicado\Pessoa;

class OrientadorObserver
{
    /**
     * Handle the Orientador "creating" event.
     *
     * @param  \App\Models\Orientador  $orientador
     * @return void
     */
    public function creating(Orientador $orientador)
    {
        if (!$orientador->externo) {
            $orientador->nome = Pessoa::obterNome($orientador->codpes);
            $orientador->email = Pessoa::email($orientador->codpes);
        }
    }

    /**
     * Handle the Orientador "created" event.
     *
     * @param  \App\Models\Orientador  $orientador
     * @return void
     */
    public function created(Orientador $orientador)
    {
        //
    }

    /**
     * Listen to the Orientador updating event.
     *
     * @param  \App\Models\Orientador  $orientador
     * @return void
     */
    public function updating(Orientador $orientador)
    {
        //
    }

    /**
     * Handle the Orientador "updated" event.
     *
     * @param  \App\Models\Orientador  $orientador
     * @return void
     */
    public function updated(Orientador $orientador)
    {
        //
    }

    /**
     * Handle the Orientador "deleted" event.
     *
     * @param  \App\Models\Orientador  $orientador
     * @return void
     */
    public function deleted(Orientador $orientador)
    {
        //
    }

    /**
     * Handle the Orientador "restored" event.
     *
     * @param  \App\Models\Orientador  $orientador
     * @return void
     */
    public function restored(Orientador $orientador)
    {
        //
    }

    /**
     * Handle the Orientador "force deleted" event.
     *
     * @param  \App\Models\Orientador  $orientador
     * @return void
     */
    public function forceDeleted(Orientador $orientador)
    {
        //
    }
}
