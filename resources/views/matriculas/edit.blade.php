@extends('master')

@section('styles')
@parent
  <style>
    #card-principal {
      border: 1px solid blue;
    }
    .bg-principal {
      background-color: LightBlue;
      border-top: 3px solid blue;
    }
    .disable-links {
      pointer-events: none;
    }
  </style>
@endsection

@section('content')
@parent
  @php
    $matricula = $objeto;
    $classe_nome = 'Matricula';
    $condicao_ativa = true;
  @endphp
  @nomenclatura(['selecao' => $matricula->selecao])
  <div class="row">
    <div class="col-md-12">
      <div class="card card-outline card-primary">
        <div class="card-header d-flex justify-content-between align-items-top">
          <div class="card-title my-0">
            @if ($modo == 'edit')
              <div style="display: flex; align-items: center; white-space: nowrap;">
                <a href="matriculas">Matrículas</a> <i class="fas fa-angle-right mx-2"></i> Matrícula nº {{ $matricula->id }}
                &nbsp; | &nbsp;
                @include('matriculas.partials.btn-enable-disable')
              </div>
            @else
              Nova Matrícula
            @endif
            para {{ $matricula->selecao->nome }} ({{ $matricula->selecao->categoria->nome }})
            @if ($matricula->selecao->categoria->nome !== 'Aluno Especial')
              - {{ $nivel }}
            @endif
            <br />
            <span class="text-muted">{{ $matricula->selecao->descricao }}</span><br />
          </div>
        </div>
        @include('common.partials.badge-instrucoes-da-selecao')
        @include('matriculas.partials.instrucoes-da-selecao')
        <div class="card-body">
          <div class="row">
            <div class="col-md-7">
              @if (!in_array($matricula->selecao->estado, ['Aguardando Início das Solicitações de Isenção de Taxa e das Inscrições/Matrículas', 'Aguardando Início das Inscrições/Matrículas']))
                @include('matriculas.show.card-principal', [    {{-- Principal --}}
                  'selecao' => $matricula->selecao
                ])
              @else
                @include('matriculas.show.card-naodisponivel')  {{-- Não Disponível --}}
              @endif
            </div>
            <div class="col-md-5">
              @if (($matricula->selecao->categoria->nome == 'Aluno Especial') && ($modo == 'edit'))
                @include('common.show.card-disciplinas')        {{-- Disciplinas --}}
              @endif
              @include('common.show.card-responsaveis', [       {{-- Responsáveis --}}
                'selecao' => $matricula->selecao
              ])
              @include('common.show.card-informativos', [       {{-- Informativos --}}
                'selecao' => $matricula->selecao
              ])
              @if ($modo == 'edit')
                @include('common.show.card-arquivos', [         {{-- Arquivos --}}
                  'selecao' => $matricula->selecao,
                  'tipoarquivo_classe_nome_plural_acentuado' => 'Matrículas',
                ])
                @if (in_array($matricula->selecao->estado, ['Período de Solicitações de Isenção de Taxa e de Inscrições/Matrículas', 'Período de Inscrições/Matrículas']) && (session('perfil') == 'usuario'))
                  @include('common.show.card-envio')            {{-- Envio --}}
                @endif
              @endif
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection

@if (isset($scroll))
  @section('javascripts_bottom')
  @parent
    <script type="text/javascript">
      $(document).ready(function() {

        var element = $('a[name="card_{{ $scroll }}"]');
        if (element.length)
          element.get(0).scrollIntoView({ behavior: 'smooth' });
      });
    </script>
  @endsection
@endif
