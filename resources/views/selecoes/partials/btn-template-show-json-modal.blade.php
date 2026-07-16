@php
  $classe_nome_plural = ClasseUtils::obterClasseNomePlural($classe_nome);
  $classe_nome_plural_acentuado = ClasseUtils::obterClasseNomePluralAcentuado($classe_nome);
@endphp

<button type="button" class="btn btn-sm btn-light text-primary" onclick="json_modal_form_{{ $classe_nome_plural }}()">
  <i class="fas fa-copy"></i> Editar Json
</button>

<!-- Modal -->
<div class="modal fade" id="json-modal-form-{{ $classe_nome_plural }}" data-backdrop="static" tabindex="-1" aria-labelledby="modalShowJson-{{ $classe_nome_plural }}" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalShowJson-{{ $classe_nome_plural }}">Formulário para {{ $classe_nome_plural_acentuado }}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="list_table_div_form">
          {{ html()->form('post', 'selecoes/' . $selecao->id . '/' . $classe_nome . '/template_json')->id('jsonForm_' . $classe_nome_plural)->open() }}
            @csrf
            @method('post')
            {{ html()->hidden('id') }}
            <style>
              #template-{{ $classe_nome_plural }} {
                height: auto !important;
                overflow-y: auto !important;
              }
            </style>
            <div class="form-group row">
              {{ html()->label('Json')->for('template-' . $classe_nome_plural)->class('col-form-label col-sm-2') }}
              <div class="col-sm-10">
                @php
                  $value = ((is_null($selecao->{'template_' . $classe_nome_plural})) ? '' : json_encode(json_decode($selecao->{'template_' . $classe_nome_plural}), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                @endphp
                {{ html()->textarea()->name('template_' . $classe_nome_plural)->value($value)->id('template-' . $classe_nome_plural)->class('form-control')->attribute('rows', '15') }}
              </div>
            </div>
            <div class="text-right">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
              <button type="button" onclick="validaJson_{{ $classe_nome_plural }}()" class="btn btn-primary">Salvar</button>
            </div>
          {{ html()->form()->close() }}
        </div>
      </div>
    </div>
  </div>
</div>

@section('javascripts_bottom')
@parent
  <script type="text/javascript">
    $(document).ready(function() {

        $('#json-modal-form-{{ $classe_nome_plural }}').on('shown.bs.modal', function() {
          $('#template-{{ $classe_nome_plural }}').focus();
        });

        json_modal_form_{{ $classe_nome_plural }} = function() {
          $('#json-modal-form-{{ $classe_nome_plural }}').modal();
        };
    });

    function validaJson_{{ $classe_nome_plural }}() {
        var json = $('#template-{{ $classe_nome_plural }}').val();
        if (json != '') {
            try {
                obj = JSON.parse(json);
            } catch (e) {
                alert('Erro: Json mal formatado!');
                alert(e);
                return;
            }
        }
        document.getElementById("jsonForm_{{ $classe_nome_plural }}").submit();
    }
  </script>
@endsection
