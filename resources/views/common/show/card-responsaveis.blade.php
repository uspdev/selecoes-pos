@section('styles')
@parent
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.css" />
  <link rel="stylesheet" href="css/responsaveis.css">
  <style>
    #card-responsaveis {
      border: 1px solid DarkGoldenRod;
      border-top: 3px solid DarkGoldenRod;
    }
  </style>
@endsection

@php
  $programa_id = $selecao->programa_id;
@endphp
@include('common.partials.modal-responsavel')
<a name="card_responsaveis"></a>
<div class="card bg-light mb-3 w-100" id="card-responsaveis">
  <div class="card-header form-inline">
    Responsáveis
  </div>
  @if (!is_null($responsaveis) && (count($responsaveis) > 0))
    <div class="card-body">
      @if ($objeto->selecao->exigePrograma())
        <div class="responsaveis-lista">
          @php
            $funcao = 'Secretários(as) do Programa';
            $programa_secretarios = array_filter($responsaveis, function ($record) use ($funcao) {
              return ($record['funcao'] == $funcao);
            });
            $programa_secretarios = !empty($programa_secretarios) ? json_decode(array_values($programa_secretarios)[0]['users'], true) : [];
          @endphp
          {{ $funcao }}<br />
          @if (count($programa_secretarios) > 0)
            <ul class="list-unstyled">
              @foreach($programa_secretarios as $user)
                <li class="modo-visualizacao">
                  <a href="javascript:void(0);" onclick="open_responsavel({{ $user['id'] }}, '{{ $funcao }}', {{ $programa_id }})" class="nome-responsavel-display"><i class="fas fa-info-circle"></i>
                    {{ $user['name'] }}
                  </a>
                </li>
              @endforeach
            </ul>
          @endif
        </div>
        <div class="responsaveis-lista">
          @php
            $funcao = 'Coordenadores(as) do Programa';
            $programa_coordenadores = array_filter($responsaveis, function ($record) use ($funcao) {
              return ($record['funcao'] == $funcao);
            });
            $programa_coordenadores = !empty($programa_coordenadores) ? json_decode(array_values($programa_coordenadores)[0]['users'], true) : [];
          @endphp
          {{ $funcao }}<br />
          @if (count($programa_coordenadores) > 0)
            <ul class="list-unstyled">
              @foreach($programa_coordenadores as $user)
                <li class="modo-visualizacao">
                  <a href="javascript:void(0);" onclick="open_responsavel({{ $user['id'] }}, '{{ $funcao }}', {{ $programa_id }})" class="nome-responsavel-display"><i class="fas fa-info-circle"></i>
                    {{ $user['name'] }}
                  </a>
                </li>
              @endforeach
            </ul>
          @endif
        </div>
      @endif
      <div class="responsaveis-lista">
        @php
          $funcao = 'Serviço de Pós-Graduação';
          $posgraduacao_servico = array_filter($responsaveis, function ($record) use ($funcao) {
            return ($record['funcao'] == $funcao);
          });
        @endphp
        {{ $funcao }}<br />
        @php
          $posgraduacao_servico = !empty($posgraduacao_servico) ? json_decode(array_values($posgraduacao_servico)[0]['users'], true) : [];
        @endphp
        @if (count($posgraduacao_servico) > 0)
          <ul class="list-unstyled">
            @foreach($posgraduacao_servico as $user)
              <li class="modo-visualizacao">
                <a href="javascript:void(0);" onclick="open_responsavel({{ $user['id'] }}, '{{ $funcao }}')" class="nome-responsavel-display"><i class="fas fa-info-circle"></i>
                  {{ $user['name'] }}
                </a>
              </li>
            @endforeach
          </ul>
        @endif
      </div>
      <div class="responsaveis-lista">
        @php
          $funcao = 'Coordenadores(as) da Pós-Graduação';
          $posgraduacao_coordenadores = array_filter($responsaveis, function ($record) use ($funcao) {
            return ($record['funcao'] == $funcao);
          });
          $posgraduacao_coordenadores = !empty($posgraduacao_coordenadores) ? json_decode(array_values($posgraduacao_coordenadores)[0]['users'], true) : [];
        @endphp
        {{ $funcao }}<br />
        @if (count($posgraduacao_coordenadores) > 0)
          <ul class="list-unstyled">
            @foreach($posgraduacao_coordenadores as $user)
              <li class="modo-visualizacao">
                <a href="javascript:void(0);" onclick="open_responsavel({{ $user['id'] }}, '{{ $funcao }}')" class="nome-responsavel-display"><i class="fas fa-info-circle"></i>
                  {{ $user['name'] }}
                </a>
              </li>
            @endforeach
          </ul>
        @endif
      </div>
    </div>
  @endif
</div>

@include('common.modal-processando')

@section('javascripts_bottom')
@parent
  <script src="https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
@endsection
