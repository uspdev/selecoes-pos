<!-- Modal que atende adicionar e editar orientadores -->
<div class="modal fade" id="modalForm" data-backdrop="static" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalLabel">Adicionar/Editar Orientadores(as)</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="list_table_div_form">
          {{ html()->form('post', '')->open() }}
            @csrf
            @method('post')
            {{ html()->hidden('id') }}
            @php
              $modo = 'create';
            @endphp
            <div class="form-group row">
              <div class="col-sm-12 d-flex align-items-center" style="gap: 10px;">
                <input class="form-control" style="width: auto; margin: 0;" name="externo" id="externo" type="checkbox" onclick="toggle_externo()">
                <label style="margin: 0;" for="externo">Externo(a) à unidade</label>
              </div>
            </div>
            <div id="grupo_interno">
              @foreach ($fields as $col)
                @if ($col['name'] == 'codpes')
                  @include('common.list-table-form-pessoa')
                @endif
              @endforeach
            </div>
            <div id="grupo_externo" style="display: none;">
              <div class="form-group row">
                {{ html()->label('Nome&nbsp;<small class="text-required">(*)</small>', 'externo_nome')->class('col-form-label col-sm-3') }}
                <div class="col-sm-9">
                  {{ html()->input('text', 'externo_nome')->class('form-control') }}
                </div>
              </div>
              <div class="form-group row">
                {{ html()->label('Número USP&nbsp;<small class="text-required">(*)</small>', 'externo_codpes')->class('col-form-label col-sm-3') }}
                <div class="col-sm-9">
                  {{ html()->input('text', 'externo_codpes')->class('form-control')->attribute('oninput', 'validateInteger(this)') }}
                </div>
              </div>
              <div class="form-group row">
                {{ html()->label('E-mail&nbsp;<small class="text-required">(*)</small>', 'externo_email')->class('col-form-label col-sm-3') }}
                <div class="col-sm-9">
                  {{ html()->input('text', 'externo_email')->class('form-control') }}
                </div>
              </div>
            </div>
            <div class="text-right">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
              <button type="submit" class="btn btn-primary">Salvar</button>
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

      var modalForm = $('#modalForm');
      var $oSelect2 = modalForm.find(':input[name^="codpes"]');
      $oSelect2.select2({
        ajax: {
          url: params => 'search/' + ($.isNumeric(params.term) ? 'codpes' : 'partenome'),
            dataType: 'json',
            delay: 1000,
            data: params => ({
              term: params.term,
              tipvinext: 'Docente'
            })
          },
          dropdownParent: modalForm,
          minimumInputLength: 4,
          theme: 'bootstrap4',
          width: '100%',
          language: 'pt_br'
      });

      // coloca o focus no select2
      // https://stackoverflow.com/questions/25882999/set-focus-to-search-text-field-when-we-click-on-select-2-drop-down
      $(document).on('select2:open', () => {
        document.querySelector('.select2-search__field').focus();
      });

      edit_form = function(id) {
        $.get('orientadores/' + id, function(row) {
          console.log(row);
          // mudando para PUT
          $('#modalForm :input').filter("input[name='_method']").val('PUT');

          // preenche o form com o valor do checkbox externo
          $('#externo').prop('checked', row.externo ? true : false);
          toggle_externo();

          // trava o checkbox externo
          $('#externo').prop('disabled', true);
          $('#externo_hidden').remove();
          if (row.externo)
            $('<input>').attr({type: 'hidden', id: 'externo_hidden', name: 'externo', value: 'on'}).appendTo('#modalForm form');    // cria um input hidden para garantir que o Controller receba 'externo' como true, já que o checkbox disabled não é enviado

          // preenche o form com os valores dos demais campos
          if ($('#externo').prop('checked')) {
            $(':input[name="externo_nome"]').val(row.nome);
            $(':input[name="externo_email"]').val(row.email);
            $(':input[name="externo_codpes"]').val(row.codpes);
          } else
            $(':input[name="externo_nome"], :input[name="externo_email"], :input[name="externo_codpes"]').val('');

          // preenche o restante do form com os valores a serem editados
          inputs = $("#modalForm :input").not(":input[type=button], :input[type=submit], :input[type=reset], input[name^='_'], #externo, :input[name^='externo_'], :input[name='codpes']");
          inputs.each(function() {
            $(this).val(row[this.name]);
          });

          // Ajustando action
          $('#modalForm').find('form').attr('action', 'orientadores/' + id);

          // Ajustando o title
          $('#modalLabel').html('Editar Orientador(a)');

          $("#modalForm").modal();
          console.log('inputs', inputs);
        });
      }

      add_form = function() {
        $("#modalForm :input").filter("input[type='text']").val('');

        $('#externo').prop('checked', false);
        toggle_externo();
        $(':input[name="codpes"]').val(null).trigger('change');

        $('#externo').prop('disabled', false);
        $('#externo_hidden').remove();

        // Ajustando action
        $('#modalForm').find('form').attr('action', 'orientadores');

        $('#modalLabel').html('Adicionar Orientador(a)');
        $('#modalForm :input').filter("input[name='_method']").val('POST');

        $("#modalForm").modal();
      }
    });

    function toggle_externo()
    {
      if ($('#externo').is(':checked')) {
        $('#grupo_interno').hide();
        $('#grupo_externo').show();
      } else {
        $('#grupo_interno').show();
        $('#grupo_externo').hide();
      }
    }
</script>
@endsection
