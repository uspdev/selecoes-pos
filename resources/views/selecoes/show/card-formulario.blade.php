@php
  $classe_nome_plural = ClasseUtils::obterClasseNomePlural($classe_nome);
  $classe_nome_plural_acentuado = ClasseUtils::obterClasseNomePluralAcentuado($classe_nome);
@endphp

@section('styles')
@parent
  <style>
    #card-selecao-formulario-{{ $classe_nome_plural }} {
      border: 1px solid coral;
      border-top: 3px solid coral;
    }
  </style>
@endsection

<a name="card_formulario_{{ $classe_nome_plural }}"></a>
<div class="card mb-3" id="card-selecao-formulario-{{ $classe_nome_plural }}">
  <div class="card-header">
    <i class="fab fa-wpforms"></i> Formulário para {{ $classe_nome_plural_acentuado }}
    <span class="small">@include('ajuda.selecoes.formulario')</span>
    @if ($condicao_ativa)
      <a href="{{ route('selecoes.createtemplate', ['selecao' => $selecao->id, 'classe_nome' => $classe_nome]) }}" class="btn btn-light btn-sm text-primary">
        <i class="fas fa-edit"></i> Editar
      </a>
    @endif
    @can('perfiladmin')
      @if (($selecao->estado != 'Encerrada') && !(str_starts_with($selecao->estado, 'Período de') && str_contains($selecao->estado, $classe_nome_plural_acentuado)))
        @include('selecoes.partials.btn-template-show-json-modal')
      @endif
    @endcan
  </div>
  <div class="card-body">
    <div class="ml-2 truncate-text">
      <strong>&nbsp; &nbsp; (tipo) Label</strong><br />
      @foreach (json_decode($selecao->{'template_' . $classe_nome_plural}) as $field => $value)
        @php
          $isRequired = (isset($value->validate) && $value->validate == 'required');
          $isConditional = !empty($value->visible_campo);
        @endphp
        @if ($isRequired || $isConditional)
          @if ($isRequired)
            <small class="text-required">(*)</small>
          @endif
          @if ($isConditional)
            <small class="text-warning">(c)</small>
          @endif
        @else
          &nbsp; &nbsp;
        @endif
        ({{ $value->type }}) {{ $value->label }}<br />
      @endforeach
    </div>
  </div>
</div>
