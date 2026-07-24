<?php

namespace App\Services;

use App\Models\Arquivo;
use App\Models\Inscricao;
use App\Models\Parametro;
use App\Models\TipoArquivo;
use App\Utils\ClasseUtils;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Uspdev\Boleto;

class BoletoService
{
    public function gerarBoleto(object $objeto, string $classe_nome, ?string $disciplina_sigla = null)
    {
        $extras = json_decode($objeto->extras, true);
        $cpf = str_replace(['-', '.'], '', $extras['cpf']);    // a lei 14.534/2023 estabeleceu que estrangeiros devem possuir CPF para cursar pós-graduação
        $parametros = Parametro::first();

        $boleto = new Boleto(config('selecoes-pos.ws_boleto_usuario'), config('selecoes-pos.ws_boleto_senha'));
        $data = array(
            'codigoUnidadeDespesa' => config('replicado.codundclg'),
            'codigoFonteRecurso' => $parametros->boleto_codigo_fonte_recurso,
            'estruturaHierarquica' => $parametros->boleto_estrutura_hierarquica,
            'dataVencimentoBoleto' => ($objeto->selecao->fluxo_continuo ? addWorkingDays(now(), $objeto->selecao->boleto_offset_vencimento) : $objeto->selecao->boleto_data_vencimento),
            'valorDocumento' => $objeto->selecao->boleto_valor,
            'tipoSacado' => 'PF',
            'cpfCnpj' => $cpf,
            'nomeSacado' => $extras['nome'],
            'codigoEmail' => $extras['e_mail'],
            'informacoesBoletoSacado' => ($objeto->selecao->exigeDisciplinas() ?
                ($objeto->selecao->fazInscricoes() ? 'Inscrição' : 'Matrícula') . ' para Aluno Especial - Disciplina ' . $disciplina_sigla :
                (!$objeto->selecao->exigePrograma() || $objeto->selecao->fazInscricoes() ? 'Inscrição para o Processo Seletivo ' . $objeto->selecao->nome : 'Matrícula para o Programa ' . $objeto->selecao->programa?->nomeCompleto())),
            'instrucoesObjetoCobranca' => 'Não receber após vencimento!',
        );

        try {
            config('app.debug') && Log::info('Gerando boleto para o CPF ' . $extras['cpf'] . '...');

            $gerar = $boleto->gerar($data);
            if ($gerar['status']) {
                $id = $gerar['value'];

                // loga situação da geração do boleto
                config('app.debug') && Log::info('$boleto->situacao(' . $id . '): ' . json_encode($boleto->situacao($id)));

                // recupera o arquivo PDF do boleto (PDF no formato binário codificado para Base64)
                $obter = $boleto->obter($id);

                // marca que o boleto foi gerado
                $objeto->boletoFoiGerado = true;

                // grava o boleto como um dos arquivos da inscrição, para o candidato poder acessar no site
                $arquivo_caminho = './arquivos/' . $objeto->created_at->year . '/' . uniqid() . Str::random(27) . '.pdf';
                $arquivo_conteudo = base64_decode($obter['value']);
                Storage::put($arquivo_caminho, $arquivo_conteudo);

                // grava informações do arquivo no banco de dados
                $arquivo = new Arquivo;
                $arquivo->user_id = \Auth::user()->id;
                $arquivo->nome_original = ClasseUtils::obterClasseNomeAbreviada($classe_nome) . $objeto->id . '_Boleto_' . (is_null($disciplina_sigla) ? '' : strtoupper($disciplina_sigla) . '_') . formatarDataHoraAtualComMilissegundos() . '.pdf';
                $arquivo->caminho = $arquivo_caminho;
                $arquivo->mimeType = 'application/pdf';
                $arquivo->tipoarquivo_id = TipoArquivo::where('classe_nome', ClasseUtils::obterClasseNomePluralAcentuado($classe_nome))->where('nome', 'Boleto(s) de Pagamento')->first()->id;
                $arquivo->save();
                $arquivo->{ ClasseUtils::obterClasseNomePlural($classe_nome) }()->attach($objeto->id, [
                    'tipo' => 'Boleto(s) de Pagamento',
                    'disciplina' => $disciplina_sigla
                ]);

                if (App::environment('local') || config('selecoes-pos.ws_boleto_cancelar')) {

                    // cancela o boleto em ambiente de desenvolvimento, ou também em produção se ligamos a chave WS_BOLETO_CANCELAR
                    config('app.debug') && Log::info('Cancelando o boleto...');
                    $boleto->cancelar($id);

                    // loga situação da geração do boleto
                    config('app.debug') && Log::info('$boleto->situacao(' . $id . '): ' . json_encode($boleto->situacao($id)));
                }

                // retorna o conteúdo do PDF
                return ['nome_original' => $arquivo->nome_original, 'conteudo' => $obter['value']];
            } else {
                Log::info('Erro ao gerar boleto... $gerar[\'value\']: ' . $gerar['value']);
                return ['nome_original' => '', 'conteudo' => ''];
            }

        } catch (Exception $e) {
            Log::info('Erro ao gerar boleto... $e->getMessage(): ' . $e->getMessage());
            return ['nome_original' => '', 'conteudo' => ''];
        }
    }
}
