@nomenclatura(['selecao' => $matricula->selecao])

<div class="alert alert-primary collapse {{ empty($hide) ? 'show' : '' }}" role="alert" id="instrucoes">
  @if ($matricula->selecao->settings()->get('instrucoes'))
    {!! nl2br(linkify($matricula->selecao->settings()->get('instrucoes'))) !!}
    <br />
  @endif
  As matrículas para {{ $objetivo }} vão de {{ formatarDataHora($matricula->selecao->matriculas_datahora_inicio) }} até {{ formatarDataHora($matricula->selecao->matriculas_datahora_fim) }}.<br />
  @if ($matricula->selecao->tem_taxa)
    Há taxa de matrícula para esta seleção.
    @if (!empty($matricula->created_at))    {{-- se existe o created_at, é porque já passou pelo "Prosseguir", e portanto conseguimos determinar se a solicitação de isenção de taxa foi aprovada ou não --}}
      @canany(['perfiladmin', 'perfilgerente', 'perfildocente'])
        O(a) candidato(a)
      @else
        Você
      @endcanany
      {{ $solicitacaoisencaotaxa_aprovada ? '' : 'não ' }}está isento(a) de pagar a taxa.
    @endif
  @else
    Não há taxa de matrícula para esta seleção.
  @endif
  @canany(['perfiladmin', 'perfilgerente', 'perfildocente'])
  @else
    <br />
    A matrícula somente poderá ser realizada pelos candidatos aprovados{{ $matricula->selecao->exigeCategoria() ? ($matricula->selecao->categoria->nome != 'Aluno Especial' ? ' no processo seletivo' : ' pelo(a)(s) ministrante(s) da(s) disciplina(s)') : '' }}.
    <br />
    Após informar seus dados, clique em "Prosseguir", envie todos os documentos exigidos e clique no botão "Enviar Matrícula".
    Sem isso, ela não será avaliada!<br />
    Caso queira renomear ou apagar um documento, passe o mouse sobre o nome dele (no celular, toque no nome dele) e clique/toque nos botões que aparecerão.<br />
  @endcanany
  <button type="button" class="close" data-toggle="collapse" data-target="#instrucoes">
    <span aria-hidden="true">&times;</span>
  </button>
</div>
