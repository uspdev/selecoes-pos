<?php

namespace App\Jobs;

use App\Models\Arquivo;
use App\Models\Inscricao;
use App\Models\Matricula;
use App\Models\Selecao;
use App\Models\SolicitacaoIsencaoTaxa;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class LimpaDados implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $data_limite;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data_limite)
    {
        $this->data_limite = $data_limite;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $data_limite = $this->data_limite;

        // transaction para não ter problema de inconsistência do DB
        $db_transaction = DB::transaction(function () use ($data_limite) {

            // apaga todas as matrículas gravadas no banco de dados até essa data
            foreach (Matricula::where('created_at', '<=', $data_limite)->get() as $matricula) {
                $matricula->arquivos()->detach();
                $matricula->pessoas()->detach();
                $matricula->delete();
            }

            // apaga todas as inscrições gravadas no banco de dados até essa data
            foreach (Inscricao::where('created_at', '<=', $data_limite)->get() as $inscricao) {
                $inscricao->arquivos()->detach();
                $inscricao->pessoas()->detach();
                $inscricao->delete();
            }

            // apaga todas as solicitações de isenção de taxa gravadas no banco de dados até essa data
            foreach (SolicitacaoIsencaoTaxa::where('created_at', '<=', $data_limite)->get() as $solicitacaoisencaotaxa) {
                $solicitacaoisencaotaxa->arquivos()->detach();
                $solicitacaoisencaotaxa->pessoas()->detach();
                $solicitacaoisencaotaxa->delete();
            }

            // apaga todas as seleções gravadas no banco de dados até essa data
            foreach (Selecao::where('created_at', '<=', $data_limite)->get() as $selecao) {
                $selecao->niveislinhaspesquisa()->detach();
                $selecao->disciplinas()->detach();
                $selecao->motivosisencaotaxa()->detach();
                $selecao->tiposarquivo()->detach();
                $selecao->arquivos()->detach();
                $selecao->delete();
            }

            // apaga todos os arquivos gravados no banco de dados até essa data
            foreach (Arquivo::where('created_at', '<=', $data_limite)->get() as $arquivo)
                $arquivo->delete();
        });

        // apaga todos os arquivos gravados no servidor até essa data
        $pasta_base = storage_path('app/arquivos');
        if (File::exists($pasta_base))
            foreach (File::directories($pasta_base) as $subpasta)
                foreach (File::files($subpasta) as $arquivo)
                    if (Carbon::createFromTimestamp(File::lastModified($arquivo))->lte($data_limite))
                        File::delete($arquivo);
    }
}
