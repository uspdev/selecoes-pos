@section('styles')
@parent
  <style>
    #card-orientadores {
      border: 1px solid brown;
      border-top: 3px solid brown;
    }
  </style>
@endsection

<a name="card_orientadores"></a>
<div class="card bg-light mb-3" id="card-orientadores">
  <div class="card-header">
    Orientadores(as)
    <span class="badge badge-pill badge-primary">{{ is_null($selecao->orientadores) ? 0 : $selecao->orientadores->count() }}</span>
    @can('selecoes.update', $selecao)
      @if ($condicao_ativa)
        @include('orientadores.partials.modal-add', ['inclusor_url' => 'selecoes', 'inclusor_objeto' => $selecao])
      @endif
    @endcan
  </div>
  <div class="card-body">
    <div class="accordion" id="accordionOrientadores">
      @if (!is_null($selecao->orientadores))
        @foreach ($selecao->orientadores as $orientador)
          <div class="card orientador-item">
            <div class="card-header" style="font-size:15px">
              @include('orientadores.show.header', [
                'inclusor_url' => 'selecoes',
                'inclusor_objeto' => $selecao
              ])
            </div>
          </div>
        @endforeach
      @endif
    </div>
  </div>
</div>
