@php
  $selecao_estado = str_replace('Inscrições/Matrículas', ($selecao->fazInscricoes() ? 'Inscrições' : 'Matrículas'), $selecao->estado);
@endphp

@if (in_array($selecao->estado, ['Em Elaboração', 'Aguardando Início das Solicitações de Isenção de Taxa e das Inscrições/Matrículas', 'Aguardando Início das Solicitações de Isenção de Taxa', 'Aguardando Início das Inscrições/Matrículas']))
  <span class="badge badge-light text-secondary"> {{ $selecao_estado }} </span>
@elseif (in_array($selecao->estado, ['Período de Solicitações de Isenção de Taxa e de Inscrições/Matrículas', 'Período de Solicitações de Isenção de Taxa', 'Período de Inscrições/Matrículas']))
  <span class="badge badge-light text-secondary"> {{ $selecao_estado }} </span>
@elseif ($selecao->estado == 'Encerrada')
  <span class="badge badge-light text-secondary"> {{ $selecao_estado }} </span>
@endif
