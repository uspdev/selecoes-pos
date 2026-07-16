@section('styles')
@parent
  {{-- https://stackoverflow.com/questions/50349017/how-can-i-change-cursor-for-disabled-button-or-a-in-bootstrap-4 --}}
  <style>
    button:disabled {
      cursor: not-allowed;
      pointer-events: all !important;
    }
    .btn-enable-disable.flex-wrap > .btn {
      margin-bottom: 4px;
      flex: 0 0 auto;
      white-space: nowrap;
    }
</style>
@endsection

@nomenclatura

<div class="btn-group btn-enable-disable flex-wrap">
  <button class="btn btn-sm {{ ($selecao->estado == 'Em Elaboração') ? 'btn-warning' : 'btn-secondary' }}" disabled name="estado" value="Em Elaboração">
    Em Elaboração
  </button>
  @php
    $estados_abreviados = [];
    if ($selecao->fluxo_continuo) {     // se é fluxo contínuo, sei com certeza também que tem taxa
      if ($selecao->fazInscricoes() && $selecao->fazMatriculas())
        $estados_abreviados[] = ['nome_das' => 'das Solicitações de Isenção de Taxa, das Inscrições e das Matrículas', 'nome_de' => 'de Solicitações de Isenção de Taxa, de Inscrições e de Matrículas'];
      elseif ($selecao->fazInscricoes())
        $estados_abreviados[] = ['nome_das' => 'das Solicitações de Isenção de Taxa e das Inscrições', 'nome_de' => 'de Solicitações de Isenção de Taxa e de Inscrições'];
      elseif ($selecao->fazMatriculas())
        $estados_abreviados[] = ['nome_das' => 'das Solicitações de Isenção de Taxa e das Matrículas', 'nome_de' => 'de Solicitações de Isenção de Taxa e de Matrículas'];
    } else {
      if ($selecao->tem_taxa)
        $estados_abreviados[] = ['nome_das' => 'das Solicitações de Isenção de Taxa', 'nome_de' => 'de Solicitações de Isenção de Taxa'];
      if ($selecao->fazInscricoes())
        $estados_abreviados[] = ['nome_das' => 'das Inscrições', 'nome_de' => 'de Inscrições'];
      if ($selecao->fazMatriculas())
        $estados_abreviados[] = ['nome_das' => 'das Matrículas', 'nome_de' => 'de Matrículas'];
    }
  @endphp
  @foreach ($estados_abreviados as $estado_abreviado)
    <button class="btn btn-sm {{ ($selecao->estado == ('Aguardando Início ' . $estado_abreviado['nome_das'])) ? 'btn-warning' : 'btn-secondary' }}" disabled name="estado" value="Aguardando Início {{ $estado_abreviado['nome_das'] }}">
      Aguardando Início {{ $estado_abreviado['nome_das'] }}
    </button>
    <button class="btn btn-sm {{ ($selecao->estado == ('Período ' . $estado_abreviado['nome_de'])) ? 'btn-success' : 'btn-secondary' }}" disabled name="estado" value="Período {{ $estado_abreviado['nome_de'] }}">
      Período {{ $estado_abreviado['nome_de'] }}
    </button>
  @endforeach
  <button class="btn btn-sm {{ ($selecao->estado == 'Encerrada') ? 'btn-danger' : 'btn-secondary' }}" disabled name="estado" value="Encerrada">
    Encerrada
  </button>
</div>
