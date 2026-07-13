@extends('master')

@section('styles')
@parent
<style>
  #card-parametros {
    border: 1px solid coral;
    border-top: 3px solid coral;
  }
</style>
@endsection

@section('content')
@parent
  <div class="row">
    <div class="col-md-7">
      {{ html()->form('post', '')->attribute('id', 'form_parametros')->open() }}
        @csrf
        @method('put')
        {{ html()->hidden('id') }}
        <div class="card mb-3 w-100" id="card-parametros">
          <div class="card-header">
            Editar Parâmetros
          </div>
          <div class="card-body">

            {{-- BLOCO CONDICIONAL: Só aparece se NÃO for parâmetro único --}}
            @if (!config('selecoes-pos.usar_parametro_unico'))
              <div class="form-group mb-4">
                <label for="programa_id">Programa:</label>
                @if(isset($programa_id))
                  @php
                    $programaAtual = $programasParaSelect->find($programa_id);
                  @endphp
                  {{-- Use um ID diferente para o campo de exibição para o JS não mexer nele --}}
                  <input type="text" class="form-control" value="{{ $programaAtual->nome ?? 'Não encontrado' }}" disabled>

                  {{-- Este é o que importa para o banco --}}
                  <input type="hidden" name="programa_id" id="programa_id" value="{{ $programa_id }}">
                @else
                  <select name="programa_id" id="programa_id" class="form-control" required>
                    <option value="" disabled selected>Selecione um programa...</option>
                    @foreach($programasParaSelect as $prog)
                      <option value="{{ $prog->id }}">{{ $prog->nome }}</option>
                    @endforeach
                  </select>
                @endif
              </div>
            @endif

            <div class="list_table_div_form">
              @php
                $modo = 'create';
              @endphp
              @foreach ($fields as $col)
                @if (empty($col['type']) || $col['type'] == 'text')
                  @include('common.list-table-form-text')
                @elseif ($col['type'] == 'number')
                  @include('common.list-table-form-number')
                @elseif ($col['type'] == 'integer')
                  @include('common.list-table-form-integer')
                @elseif ($col['type'] == 'radio')
                  @include('common.list-table-form-radio')
                @elseif ($col['type'] == 'select')
                  @include('common.list-table-form-select')
                @endif
              @endforeach
              <div class="text-right">
                <button type="submit" class="btn btn-primary">Salvar</button>
              </div>
            </div>
          </div>
        </div>
      {{ html()->form()->close() }}
    </div>
  </div>
@endsection

@section('javascripts_bottom')
@parent
  <script src="js/functions.js"></script>
  <script type="text/javascript">
    $(document).ready(function() {
      $(this).find(':input[type=text], :input[type=number]').filter(':visible:first').focus();

      var parametros = {!! json_encode($parametros) !!};
      var inputs = $("#form_parametros :input").not(":input[type=button], :input[type=submit], :input[type=reset], input[name^='_']");

      inputs.each(function() {
        // SÓ PREENCHE se o campo não for o programa_id OU se o programa_id estiver vazio
        // Isso evita que o JS limpe o que o Controller enviou
        if (this.name === 'programa_id' && $(this).val() !== "")
            return; // pula para o próximo input

        if (parametros[this.name] !== undefined) {
          if ($(this).attr('type') === 'radio') {
              if ($(this).val() === String(parametros[this.name]))
                  $(this).prop('checked', true);
          } else {
            $(this).val(parametros[this.name]);
            if ($(this).attr('oninput') == 'validateNumber(this)')
              $(this).val(formatarDecimal($(this).val()));
          }
        }
      });
    });
  </script>
@endsection
