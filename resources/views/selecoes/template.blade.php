@extends('master')
@section('content')
@parent
  @php
    $classe_nome_plural_acentuado = ClasseUtils::obterClasseNomePluralAcentuado($classe_nome);
    $condicao_iniciada = (str_starts_with($selecao->estado, 'Período de') && str_contains($selecao->estado, $classe_nome_plural_acentuado)) || ($selecao->estado == 'Encerrada');
  @endphp
  @include('common.modal-processando')
  <div class="row">
    <div class="col-md-12">
      {{ html()->form('post', route('selecoes.storetemplate', ['selecao' => $selecao->id, 'classe_nome' => $classe_nome]))->id('template-form')->open() }}
        @csrf
        @method('post')
        {{ html()->hidden('id') }}
        <div class="card card-outline card-primary">
          <div class="card-header">
            <div class="card-title form-inline my-0">
              Seleções <i class="fas fa-angle-right mx-2"></i>
              <a href="selecoes/edit/{{ $selecao->id }}">Seleção nº {{ $selecao->id }}</a>
              @if ($selecao->exigeCategoria())
                &nbsp;({{ $selecao->categoria->nome }})
              @endif
              &nbsp; | &nbsp;  Formulário para {{ $classe_nome_plural_acentuado }} &nbsp; | &nbsp; &nbsp;
              @include('selecoes.partials.btn-template-novocampo-modal')
            </div>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="container-fluid">
                <div class="row">
                  <div class="col-12">
                    @if ($template)
                      <div id="template-header" class="form-row">
                        <div class="col"><strong>Campo</strong></div>
                        @foreach ($selecao->getTemplateFields() as $field)
                          <div class="col"><strong>{{ ucfirst($field) }}</strong></div>
                        @endforeach
                        <div style="flex: 0 0 170px; max-width: 170px;"></div>
                      </div>
                      @php
                        $i = 0;
                      @endphp
                      @foreach ($template as $tkey => $tvalue)
                        <div class="form-row mt-2" id="linha_{{ $i }}">
                          <div class="col truncate-text">{{ $tkey }}</div>
                          @php
                            $disable_functions = '';
                            if (in_array($tkey, ['nome', 'tipo_de_documento', 'numero_do_documento', 'cpf', 'celular', 'e_mail']))    // estes campos devem ser sempre obrigatórios, pois outras funcionalidades do sistema dependem dessa condição
                              $disable_functions = ' disabled';
                          @endphp
                          @foreach ($selecao->getTemplateFields() as $field)
                            <div class="col">
                              @isset($tvalue[$field])
                                @switch($field)
                                  @case('type')
                                    <select class="form-control" name="template[{{ $tkey }}][{{ $field }}]" onchange="javascript: mudarCampoInputTextarea(this.name, {{ Gate::allows('perfiladmin') }});">
                                      <option value='text' {{ $tvalue[$field] == 'text' ? 'selected' : '' }}>Texto</option>
                                      <option value='textarea' {{ $tvalue[$field] == 'textarea' ? 'selected' : '' }}>Texto (várias linhas)</option>
                                      <option value='select' {{ $tvalue[$field] == 'select' ? 'selected' : '' }}>Caixa de Seleção</option>
                                      <option value='date' {{ $tvalue[$field] == 'date' ? 'selected' : '' }}>Data</option>
                                      <option value='number' {{ $tvalue[$field] == 'number' ? 'selected' : '' }}>Número</option>
                                      <option value='email' {{ $tvalue[$field] == 'email' ? 'selected' : '' }}>E-mail</option>
                                      <option value='radio' {{ $tvalue[$field] == 'radio' ? 'selected' : '' }}>Botão de Opção</option>
                                      <option value='checkbox' {{ $tvalue[$field] == 'checkbox' ? 'selected' : '' }}>Caixa de Verificação</option>
                                      <option value='label' {{ $tvalue[$field] == 'label' ? 'selected' : '' }}>Informativo</option>
                                    </select>
                                    @break
                                  @case('validate')
                                    <select class="form-control" name="template[{{ $tkey }}][{{ $field }}]"{{ $disable_functions }}>
                                      <option value='' {{ $tvalue[$field] == '' ? 'selected' : '' }}>Sem validação</option>
                                      <option value='required' {{ $tvalue[$field] == 'required' ? 'selected' : '' }}>Obrigatório</option>
                                      {{-- <option value='required|integer' {{ $tvalue[$field] == 'required|integer' ? 'selected' : '' }}>Obrigatório - Somente números</option> --}}
                                    </select>
                                    @break
                                  @case('can')
                                    <select class="form-control" name="template[{{ $tkey }}][{{ $field }}]"{{ $disable_functions }}>
                                      <option value='' {{ $tvalue[$field] == '' ? 'selected' : '' }}>Exibido para todos</option>
                                      <option value='gerente' {{ $tvalue[$field] == 'gerente' ? 'selected' : '' }}>Somente Gerentes</option>
                                    </select>
                                    @break
                                  @case('value')
                                    @can('perfiladmin')
                                      <input class="form-control" name="template[{{ $tkey }}][{{ $field }}]" value="{{ is_array($tvalue[$field]) ? json_encode($tvalue[$field], JSON_UNESCAPED_UNICODE) : $tvalue[$field] ?? '' }}">
                                    @else
                                      <input class="form-control" name="template[{{ $tkey }}][{{ $field }}]" value="{{ is_array($tvalue[$field]) ? json_encode($tvalue[$field], JSON_UNESCAPED_UNICODE) : $tvalue[$field] ?? '' }}" type="hidden">
                                    @endcan
                                    <a href="{{ route('selecoes.createtemplatevalue', ['selecao' => $selecao->id, 'classe_nome' => $classe_nome, 'campo' => $tkey]) }}" class="btn btn-primary btn-sm">
                                      <i class="fas fa-edit"></i> Editar Lista
                                    </a>
                                    @break
                                  @default
                                    <input class="form-control" name="template[{{ $tkey }}][{{ $field }}]" value="{{ is_array($tvalue[$field]) ? json_encode($tvalue[$field], JSON_UNESCAPED_UNICODE) : $tvalue[$field] ?? '' }}">
                                @endswitch
                              @endisset
                              @if(empty($tvalue[$field]) && !isset($tvalue[$field]))
                                @switch($field)
                                  @case('validate')
                                    <select class="form-control" name="template[{{ $tkey }}][{{ $field }}]"{{ $disable_functions }}>
                                      <option value=''>Sem validação</option>
                                      <option value='required'>Obrigatório</option>
                                      <option value='required|integer'>Obrigatório - Somente números</option>
                                    </select>
                                    @break
                                  @case('can')
                                    <select class="form-control" name="template[{{ $tkey }}][{{ $field }}]"{{ $disable_functions }}>
                                      <option value=''>Exibido para todos</option>
                                      <option value='gerente'>Somente Gerente</option>
                                    </select>
                                    @break
                                  @case('value')
                                    @if (in_array($tvalue['type'], ['select', 'radio']))
                                      @can('perfiladmin')
                                        <input class="form-control" name="template[{{ $tkey }}][{{ $field }}]">
                                      @else
                                        <input class="form-control" name="template[{{ $tkey }}][{{ $field }}]" type="hidden">
                                      @endcan
                                      <a href="{{ route('selecoes.createtemplatevalue', ['selecao' => $selecao->id, 'classe_nome' => $classe_nome, 'campo' => $tkey]) }}" class="btn btn-primary btn-sm">
                                        <i class="fas fa-edit"></i> Editar Lista
                                      </a>
                                    @else
                                      <input class="form-control" name="template[{{ $tkey }}][{{ $field }}]" value="">
                                    @endif
                                    @break
                                  @default
                                    <input class="form-control" name="template[{{ $tkey }}][{{ $field }}]" value="">
                                @endswitch
                              @endif
                            </div>
                          @endforeach
                          <div class="text-right text-nowrap" style="flex: 0 0 170px; max-width: 170px;">
                            @if (!$condicao_iniciada)
                              <button class="btn btn-danger" type="button" onclick="apaga_campo(this)"{{ $disable_functions }}>Apagar</button>
                            @endif
                            <input type="hidden" name="template[{{ $tkey }}][order]" id="index[{{ $i }}]" value="{{ $i }}">
                            <button class="btn btn-success" type="button" onclick="move(this, 1)">&#8679;</button>
                            <button class="btn btn-success" type="button" onclick="move(this, 0)">&#8681;</button>
                          </div>
                        </div>
                        @php
                          $i++;
                        @endphp
                      @endforeach
                    @else
                      Não existe formulário para {{ Str::lower($classe_nome_plural_acentuado) }} para essa seleção.
                      <br />
                    @endif
                    <br />
                    @if ($template && !$condicao_iniciada)
                      <button class="btn btn-primary ml-1" type="submit">Salvar</button>
                    @endif
                    <a class="btn btn-secondary" href="{{ route('selecoes.edit', ['selecao' => $selecao->id]) }}">Voltar</a>
                  </div>
                </div>
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
      $('select[name$="][type]"]').each(function () {
        $(mudarCampoInputTextarea($(this).prop('name'), {{ Gate::allows('perfiladmin') }}));
      });

      $('#template-form').on('submit', function() {
        $(this).find(':disabled').prop('disabled', false);    // desliga o disabled de todos os campos do form, pois campos disabled não sobem no submit e precisamos que eles subam junto com os demais
      });
    });

    function apaga_campo(r) {
      if (confirm('Tem certeza que deseja deletar?')) {
        var row = r.parentNode.parentNode;
        row.remove();
        $('#modal_processando').modal('show');
        var form = document.getElementById('template-form');
        form.requestSubmit();
      }
    }

    function move(r, up) {
      var head = 'template-header';
      var tail = 'template-new';
      var form = document.getElementById('template-form');
      var row = r.parentNode.parentNode;
      var i = parseInt(row.id.split('_')[1]);
      if (up) {
        var sibling = row.previousElementSibling;
        if (sibling.id != head) {
          row.parentNode.insertBefore(row, sibling);
          $('#modal_processando').modal('show');
          $('input[id="index[' + i + ']"]').val(i - 1);
          $('input[id="index[' + (i - 1) + ']"]').val(i);
          form.requestSubmit();
        }
      } else {
        var sibling = row.nextElementSibling;
        if (sibling.id) {
          row.parentNode.insertBefore(row, sibling.nextSibling);
          $('#modal_processando').modal('show');
          $('input[id="index[' + i + ']"]').val(i + 1);
          $('input[id="index[' + (i + 1) + ']"]').val(i);
          form.requestSubmit();
        }
      }
    }
  </script>
@endsection
