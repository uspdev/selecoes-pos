<div class="row">
  <div class="col-md-12 form-inline">
    <span class="h4 mt-2">Tipos de Documento</span>
    @can('tiposarquivo.create')
      &nbsp; &nbsp;
      <a href="{{ route('tiposarquivo.create') }}" class="btn btn-sm btn-success">
        <i class="fas fa-plus"></i> Novo
      </a>
    @endcan
  </div>
</div>

<table class="table table-sm my-0 ml-3">
  @php
    $classe_nome_anterior = '';
  @endphp
  @foreach ($tiposarquivo as $tipoarquivo)
    @if ($tipoarquivo->classe_nome != $classe_nome_anterior)
      <tr>
        <td colspan="2">
          {{ $tipoarquivo->classe_nome }}
        </td>
      </tr>
      @php
        $classe_nome_anterior = $tipoarquivo->classe_nome;
      @endphp
    @endif
    {{-- Mostra o conteúdo de um tipo de arquivo --}}
    <tr>
      <td>&nbsp;</td>
      <td>
        <div>
          <a name="{{ \Str::lower($tipoarquivo->id) }}" class="font-weight-bold" style="text-decoration: none;">{{ $tipoarquivo->nome }}</a>
          @if (!str_starts_with($tipoarquivo->nome, 'Boleto(s) de Pagamento'))
            @can('tiposarquivo.update')
              @include('tiposarquivo.partials.btn-edit')
            @endcan
            @can('tiposarquivo.delete')
              @include('tiposarquivo.partials.btn-delete')
            @endcan
          @endif
          @if ($tipoarquivo->classe_nome !== 'Seleções')
            @include('tiposarquivo.partials.detalhes')
          @endif
        </div>
      </td>
    </tr>
  @endforeach
</table>
