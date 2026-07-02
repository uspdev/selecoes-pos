@nomenclatura(['selecao' => $inscricao->selecao])

<div class="alert alert-primary collapse {{ empty($hide) ? 'show' : '' }}" role="alert" id="instrucoes">
  @if ($inscricao->selecao->settings()->get('instrucoes'))
    {!! nl2br(linkify($inscricao->selecao->settings()->get('instrucoes'))) !!}
    <br />
  @endif
  As inscrições para {{ $objetivo }} vão de {{ formatarDataHora($inscricao->selecao->inscricoesmatriculas_datahora_inicio) }} até {{ formatarDataHora($inscricao->selecao->inscricoesmatriculas_datahora_fim) }}.<br />
  @if ($inscricao->selecao->tem_taxa)
    Há taxa de inscrição para esta seleção.
    @if (!empty($inscricao->created_at))    {{-- se existe o created_at, é porque já passou pelo "Prosseguir", e portanto conseguimos determinar se a solicitação de isenção de taxa foi aprovada ou não --}}
      Você {{ $solicitacaoisencaotaxa_aprovada ? '' : 'não ' }}está isento(a) de pagar a taxa.
    @endif
  @else
    Não há taxa de inscrição para esta seleção.
  @endif
  <br />
  Após informar seus dados, clique em "Prosseguir", envie todos os documentos exigidos e clique no botão "Enviar Inscrição".
  Sem isso, ela não será avaliada!<br />
  Caso queira renomear ou apagar um documento, passe o mouse sobre o nome dele (no celular, toque no nome dele) e clique/toque nos botões que aparecerão.<br />
  <button type="button" class="close" data-toggle="collapse" data-target="#instrucoes">
    <span aria-hidden="true">&times;</span>
  </button>
</div>
