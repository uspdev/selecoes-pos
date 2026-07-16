@section('styles')
@parent
  <style>
    #card-inscricao-principal {
      border: 1px solid coral;
      border-top: 3px solid coral;
    }
  </style>
@endsection

{{ html()->form('post', $data->url . (($modo == 'edit') ? ('/edit/' . $inscricao->id) : '/create'))
  ->attribute('id', 'form_principal')
  ->attribute('novalidate', '')          // pois faço minha validação manual em $('#form_principal').on('submit'
  ->open() }}
  @csrf
  @method($modo == 'edit' ? 'put' : 'post')
  {{ html()->hidden('id') }}
  <input type="hidden" id="selecao_id" name="selecao_id" value="{{ $inscricao->selecao->id }}">
  @if ($inscricao->selecao->categoria->nome != 'Aluno Especial')
    <input type="hidden" id="extras[nivel]" name="extras[nivel]" value="{{ json_decode($inscricao->extras)->nivel }}">
  @endif
  <div class="card mb-3 w-100" id="card-inscricao-principal">
    <div class="card-header">
      Informações básicas
    </div>
    <div class="card-body">
      <div class="list_table_div_form">
        @if (isset($form))
          @foreach ($form as $input)
            @if (is_array($input))
              <div class="form-group row">
                @foreach ($input as $element)
                  {!! $element !!}<br />
                @endforeach
              </div>
            @endif
          @endforeach
        @endif
      </div>
      @if (str_starts_with($selecao->estado, 'Período de') && str_contains($selecao->estado, 'Inscrições') && (session('perfil') == 'usuario'))
        <div class="text-right">
          @if($inscricao->estado !== 'Aprovada')
            <button type="submit" class="btn btn-primary">{{ ($modo == 'edit' ) ? 'Salvar' : 'Prosseguir' }}</button>
          @endif
        </div>
      @endif
    </div>
  </div>
{{ html()->form()->close() }}

@section('javascripts_bottom')
@parent
  <script src="js/functions.js"></script>
  <script type="text/javascript">
    $(document).ready(function() {

      $('#form_principal').find(':input:visible:first').focus();

      $('#form_principal :input').on('input change changeDate dateChanged', function() {
        this.setCustomValidity('');
      });

      $('input[id="extras\[data\]"], input[id^="extras\[data_"]').each(function() {
        $(this).mask('00/00/0000');
      });

      $('input[id="extras\[cpf\]"], input[id^="extras\[cpf_"]').each(function() {
        $(this).mask('000.000.000-00');
      });

      $('input[id="extras\[cep\]"], input[id^="extras\[cep_"]').each(function() {
        $(this).mask('00000-000');
      });

      $('input[id="extras\[celular\]"], input[id^="extras\[celular_"]').each(function() {
        $(this).mask('(00) 00000-0000');
      });

      $('select[id="extras\[tipo_de_documento\]"]').change(function () {
        if ($(this).val().toLowerCase().includes('passaporte') ||
            $(this).val().toLowerCase().includes('rne') ||
            $(this).val().toLowerCase().includes('crnm')) {
          $('#uf_de_nascimento_required').hide();
          $('select[id="extras\[uf_de_nascimento\]"]').removeAttr('required');
        } else {
          $('#uf_de_nascimento_required').show();
          $('select[id="extras\[uf_de_nascimento\]"]').attr('required', true);
        }
      });

      $('select[id="extras\[tipo_de_documento\]"]').trigger('change');
    });

    $('#form_principal').on('submit', function(event) {
      var form_valid = true;

      $('#form_principal [required]').each(function () {
        if (!this.validity.valid) {
          form_valid = false;
          switch (this.type) {
            case 'email':
              if (this.value !== '')
                return mostrar_validacao(this, 'E-mail inválido');
              else
                return mostrar_validacao(this, 'Favor preencher este campo');
            case 'radio':
              if ($('input[name="' + this.name + '"]:checked').length === 0)
                return mostrar_validacao(this, 'Favor selecionar uma opção');
              break;
            case 'checkbox':
              if (!this.checked)
                return mostrar_validacao(this, 'Favor marcar esta opção');
              break;
            default:
              if (this.value === '')
                return mostrar_validacao(this, 'Favor preencher este campo');
          }
        } else if ((this.id == 'extras[cpf]') || this.id.startsWith('extras[cpf_'))
          if (!validar_cpf(this.value)) {
            form_valid = false;
            return mostrar_validacao(this, 'CPF inválido');
          }
      });

      $('input[id="extras\[data\]"], input[id^="extras\[data_"]').each(function() {
        if (!validar_data(this.value)) {
          form_valid = false;
          return mostrar_validacao(this, 'Data inválida');
        }
      });

      if (!form_valid)
        event.preventDefault();
    });

    $('#password').on('input', function () {
      validar_forca_senha($(this).val());
    });

    function consultar_cep(field_name)
    {
      var cep = $('input[id="extras\[' + field_name + '\]"]').val().replace('-', '').trim();
      if (cep !== '') {
        $('#consultar_' + field_name).text('Consultando ...');
        $.ajax({
          url: '{{ route("consulta.cep") }}',
          type: 'get',
          data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            cep: cep
          },
          success: function(data) {
            var field_suffix = '';
            if (field_name.includes('_'))
              field_suffix = '_' + field_name.split('_')[1];

            $('input[id="extras\[endereco_residencial' + field_suffix + '\]"]').val(data.logradouro);
            $('input[id="extras\[bairro' + field_suffix + '\]"]').val(data.bairro);
            $('input[id="extras\[cidade' + field_suffix + '\]"]').val(data.localidade);
            $('select[id="extras\[uf' + field_suffix + '\]"]').val(data.uf.toLowerCase());

            $('#consultar_' + field_name).text('Consultar CEP');
          },
          error: function(xhr, status, error) {
            $('#consultar_' + field_name).text('Consultar CEP');

            if (xhr.responseJSON && xhr.responseJSON.error)
              window.alert(xhr.responseJSON.error);
            else if (xhr.responseText)
              window.alert(xhr.responseText);
          }
        });
      }
    }
  </script>

  @if($inscricao->estado === 'Aprovada')
<script>
  $(document).ready(function() {
    $('#form_principal')
      .find('input:not([type="hidden"]), textarea')
      .prop('readonly', true);

    $('#form_principal')
      .find('select, input[type="checkbox"], input[type="radio"], input[type="file"]')
      .prop('disabled', true);
  });
</script>
@endif

@endsection
