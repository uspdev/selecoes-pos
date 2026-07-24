@extends('master')

@section('styles')
@parent
<style>
  .disable-links {
    pointer-events: none;
  }
</style>
@endsection

@section('content')
@parent
  @php
    $selecao = $objeto;
    $classe_nome = 'Selecao';
    $condicao_ativa = ($selecao->estado != 'Encerrada');
  @endphp
  <div class="row">
    <div class="col-md-12">
      <div class="card card-outline card-primary">
        <div class="card-header">
          <div class="card-title form-inline my-0">
            @if ($modo == 'edit')
              <div style="display: flex; align-items: flex-start; white-space: nowrap;">
                <div style="margin-top: -3px;">
                  <a href="selecoes">Seleções</a> <i class="fas fa-angle-right mx-2"></i> Seleção nº {{ $selecao->id }}
                  &nbsp; | &nbsp;
                </div>
                @include('selecoes.partials.btn-enable-disable')
              </div>
            @else
              Nova Seleção
            @endif
          </div>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-7">
              @include('selecoes.show.card-principal')                               {{-- Principal --}}
              @if ($modo == 'edit')
                @if ($selecao->tem_taxa)
                  @include('selecoes.show.card-formulario', [                        {{-- Formulário para Solicitações de Isenção de Taxa --}}
                    'classe_nome' => 'SolicitacaoIsencaoTaxa',
                  ])
                @endif
                @if ($selecao->fazInscricoes())
                  @include('selecoes.show.card-formulario', [                        {{-- Formulário para Inscrições --}}
                    'classe_nome' => 'Inscricao',
                  ])
                @endif
                @if ($selecao->fazMatriculas())
                  @include('selecoes.show.card-formulario', [                        {{-- Formulário para Matrículas --}}
                    'classe_nome' => 'Matricula',
                  ])
                @endif
              @endif
            </div>
            <div class="col-md-5">
              @if ($modo == 'edit')
                @if ($selecao->exigeNivel() && $selecao->exigeLinhaPesquisa())
                  @include('selecoes.show.card-niveislinhaspesquisa')                {{-- Níveis + Linhas de Pesquisa/Temas --}}
                @endif
                @if ($selecao->exigeDisciplinas())
                  @include('selecoes.show.card-disciplinas')                         {{-- Disciplinas --}}
                @endif
                @if ($selecao->tem_taxa)
                  @include('selecoes.show.card-motivosisencaotaxa')                  {{-- Motivos de Isenção de Taxa --}}
                @endif
                @include('common.show.card-arquivos', [                              {{-- Arquivos --}}
                  'tipoarquivo_classe_nome_plural_acentuado' => 'Seleções',
                ])
                @if ($selecao->tem_taxa)
                  @include('selecoes.show.card-tiposarquivo', [                      {{-- Tipos de Arquivo nas Solicitações de Isenção de Taxa --}}
                    'tipoarquivo_classe_nome_plural_acentuado' => 'Solicitações de Isenção de Taxa',
                    'tipoarquivo_classe_nome' => 'SolicitacaoIsencaoTaxa',
                    'tiposarquivo' => $tiposarquivo_solicitacaoisencaotaxa
                  ])
                @endif
                @if ($selecao->fazInscricoes())
                  @include('selecoes.show.card-tiposarquivo', [                      {{-- Tipos de Arquivo nas Inscrições --}}
                    'tipoarquivo_classe_nome_plural_acentuado' => 'Inscrições',
                    'tipoarquivo_classe_nome' => 'Inscricao',
                    'tiposarquivo' => $tiposarquivo_inscricao
                  ])
                @endif
                @if ($selecao->fazMatriculas())
                  @include('selecoes.show.card-tiposarquivo', [                      {{-- Tipos de Arquivo nas Matrículas --}}
                    'tipoarquivo_classe_nome_plural_acentuado' => 'Matrículas',
                    'tipoarquivo_classe_nome' => 'Matricula',
                    'tiposarquivo' => $tiposarquivo_matricula
                  ])
                @endif
                @if ($selecao->tem_taxa)
                  @include('selecoes.show.card-solicitacoesisencaotaxa')             {{-- Solicitações de Isenção de Taxa --}}
                @endif
                @if ($selecao->fazInscricoes())
                  @include('selecoes.show.card-inscricoes')                          {{-- Inscrições --}}
                @endif
                @if ($selecao->fazMatriculas())
                  @include('selecoes.show.card-matriculas')                          {{-- Matrículas --}}
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

        var selecao = {!! json_encode($selecao) !!};
        var inputs = $("#form_principal :input").not(":input[type=button], :input[type=submit], :input[type=reset], input[name^='_']");

        inputs.each(function() {
          if ($(this).attr('type') === 'radio') {
              if ($(this).val() === String(selecao[this.name]))
                  $(this).prop('checked', true);
          }
        });
      });
    </script>
  @endsection
@endif
