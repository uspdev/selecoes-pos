@php
  $selecao_estado = str_replace('Inscrições/Matrículas', ($selecao->fazInscricoes() ? 'Inscrições' : 'Matrículas'), $selecao->estado);
@endphp

@if (in_array($selecao->estado, ['Em Elaboração', 'Aguardando Início das Solicitações de Isenção de Taxa e das Inscrições/Matrículas', 'Aguardando Início das Solicitações de Isenção de Taxa', 'Aguardando Início das Inscrições/Matrículas']))
  <span class="text-warning" data-toggle="tooltip" title="{{ $selecao_estado }}"> <i class="fas fa-circle"></i> </span>
@elseif (in_array($selecao->estado, ['Período de Solicitações de Isenção de Taxa e de Inscrições/Matrículas', 'Período de Solicitações de Isenção de Taxa', 'Período de Inscrições/Matrículas']))
  <span class="text-success" data-toggle="tooltip" title="{{ $selecao_estado }}"> <i class="fas fa-circle"></i> </span>
@elseif ($selecao->estado == 'Encerrada')
  <span class="text-danger" data-toggle="tooltip" title="{{ $selecao_estado }}"> <i class="fas fa-circle"></i> </span>
@endif
