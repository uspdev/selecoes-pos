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
      {{ html()->form('post', route('selecoes.storetemplatevalue', ['selecao' => $selecao->id, 'classe_nome' => $classe_nome, 'campo' => $field]))->id('valuetemplate-form')->open() }}
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
              &nbsp; | &nbsp;  Formulário para {{ $classe_nome_plural_acentuado }} <i class="fas fa-angle-right mx-2"></i> {{ str_replace('_', ' ', ucwords($field)) }} &nbsp; | &nbsp; &nbsp;
              @include('selecoes.partials.btn-template-novocampolista-modal')
            </div>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="container-fluid">
                <div class="row">
                  <div class="col-12">
                    @if (isset($template[$field]) && isset($template[$field]['value']) && is_array($template[$field]['value']))
                      <div id="template-header" class="form-row">
                        <div class="col-2"><strong>Valor</strong></div>
                        <div class="col-3"><strong>Label</strong></div>
                        <div class="col"></div>
                      </div>
                      @php
                        $i = 0;
                      @endphp
                      @foreach ($template[$field]['value'] as $tkey => $tvalue)
                        <div class="form-row mt-2" id="linha_{{ $i }}">
                          <div class="col-2 truncate-text">
                            {{ $tvalue['value'] }}
                          </div>
                          <div class="col-3">
                            <input class="form-control" name="value[{{ $tkey }}][label]" value="{{ $tvalue['label'] }}">
                          </div>
                          <div class="col">
                            @if (!$condicao_iniciada)
                              <button class="btn btn-danger" type="button" onclick="apaga_campo(this)">Apagar</button>
                            @endif
                            <input type="hidden" name="value[{{ $tkey }}][order]" id="index[{{ $i }}]" value="{{ $i }}">
                            <button class="btn btn-success" type="button" onclick="move(this, 1)">&#8679;</button>
                            <button class="btn btn-success" type="button" onclick="move(this, 0)">&#8681;</button>
                          </div>
                        </div>
                        @php
                          $i++;
                        @endphp
                      @endforeach
                      <br />
                      @if (!$condicao_iniciada)
                        <button class="btn btn-primary ml-1" type="submit">Salvar</button>
                      @endif
                    @else
                      Não existe {{ str_replace('_', ' ', $field) }} para esse formulário para {{ Str::lower($classe_nome_plural_acentuado) }}.
                      <br />
                      <br />
                    @endif
                    <a class="btn btn-secondary" href="{{ route('selecoes.createtemplate', ['selecao' => $selecao, 'classe_nome' => $classe_nome]) }}">Voltar</a>
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
    function apaga_campo(r) {
      if (confirm('Tem certeza que deseja deletar?')) {
        var row = r.parentNode.parentNode;
        row.remove();
        $('#modal_processando').modal('show');
        var form = document.getElementById('valuetemplate-form');
        form.requestSubmit();
      }
    }

    function move(r, up) {
      var head = 'template-header';
      var tail = 'template-new';
      var form = document.getElementById('valuetemplate-form');
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
