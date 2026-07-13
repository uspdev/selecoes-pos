<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CopyTiposArquivoDataFromMatriculasToInscricoes extends Migration
{
    public function up()
    {
        $registroOriginal = DB::table('tiposarquivo')->where('classe_nome', 'Matrículas')->where('nome', 'Boleto(s) de Pagamento - Disciplinas Removidas')->first();
        if ($registroOriginal) {
            $novoRegistro = (array) $registroOriginal;
            unset($novoRegistro['id']);
            $novoRegistro['classe_nome'] = 'Inscrições';
            $novoRegistro['created_at'] = now();
            $novoRegistro['updated_at'] = now();
            $novoTipoArquivoId = DB::table('tiposarquivo')->insertGetId($novoRegistro);

            $categoriasOriginais = DB::table('tipoarquivo_categoria')->where('tipoarquivo_id', $registroOriginal->id)->get();
            $novasCategorias = [];
            foreach ($categoriasOriginais as $categoria)
                $novasCategorias[] = ['tipoarquivo_id' => $novoTipoArquivoId, 'categoria_id' => $categoria->categoria_id, 'created_at' => now(), 'updated_at' => now()];
            if (!empty($novasCategorias))
                DB::table('tipoarquivo_categoria')->insert($novasCategorias);
        }
    }

    public function down()
    {
        $novoRegistro = DB::table('tiposarquivo')->where('classe_nome', 'Inscrições')->where('nome', 'Boleto(s) de Pagamento - Disciplinas Removidas')->first();
        if ($novoRegistro) {
            DB::table('tipoarquivo_categoria')->where('tipoarquivo_id', $novoRegistro->id)->delete();
            DB::table('tiposarquivo')->where('id', $novoRegistro->id)->delete();
        }
    }
}
