@nomenclatura(['selecao' => $solicitacaoisencaotaxa->selecao])

<div class="alert alert-primary collapse {{ empty($hide) ? 'show' : '' }}" role="alert" id="instrucoes">
  @if ($solicitacaoisencaotaxa->selecao->settings()->get('instrucoes'))
    {!! nl2br(linkify($solicitacaoisencaotaxa->selecao->settings()->get('instrucoes'))) !!}
    <br />
  @endif
  As solicitações de isenção de taxa para {{ $objetivo }} vão de {{ formatarDataHora($solicitacaoisencaotaxa->selecao->solicitacoesisencaotaxa_datahora_inicio) }} até {{ formatarDataHora($solicitacaoisencaotaxa->selecao->solicitacoesisencaotaxa_datahora_fim) }}.<br />
  Após informar seus dados, clique em "Prosseguir", envie todos os documentos exigidos e clique no botão "Enviar Solicitação".
  Sem isso, ela não será avaliada!<br />
  Caso queira renomear ou apagar um documento, passe o mouse sobre o nome dele (no celular, toque no nome dele) e clique/toque nos botões que aparecerão.<br />
  <button type="button" class="close" data-toggle="collapse" data-target="#instrucoes">
    <span aria-hidden="true">&times;</span>
  </button>
</div>
