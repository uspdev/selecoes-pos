@if (($selecao->estado == 'Em Elaboração') || str_starts_with($selecao->estado, 'Aguardando Início das'))
  <span class="badge badge-light text-secondary"> {{ $selecao->estado }} </span>
@elseif (str_starts_with($selecao->estado, 'Período de'))
  <span class="badge badge-light text-secondary"> {{ $selecao->estado }} </span>
@elseif ($selecao->estado == 'Encerrada')
  <span class="badge badge-light text-secondary"> {{ $selecao->estado }} </span>
@endif
