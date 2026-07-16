@section('styles')
@parent
  <style>
    #card-solicitacaoisencaotaxa-principal {
      border: 1px solid coral;
      border-top: 3px solid coral;
    }
  </style>
@endsection

{{ html()->form('post', $data->url . (($modo == 'edit') ? ('/edit/' . $solicitacaoisencaotaxa->id) : '/create'))
  ->attribute('id', 'form_principal_solicitacaoisencaotaxa')
  ->attribute('novalidate', '')          // pois faço minha validação manual em $('#form_principal_solicitacaoisencaotaxa').on('submit'
  ->open() }}
  @csrf
  @method($modo == 'edit' ? 'put' : 'post')
  {{ html()->hidden('id') }}
  <input type="hidden" id="selecao_id" name="selecao_id" value="{{ $solicitacaoisencaotaxa->selecao->id }}">
  <div class="card mb-3 w-100" id="card-solicitacaoisencaotaxa-principal">
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
      @if (str_starts_with($selecao->estado, 'Período de Solicitações de Isenção de Taxa') && session('perfil') == 'usuario')
        <div class="text-right">
          <button type="submit" class="btn btn-primary">{{ ($modo == 'edit' ) ? 'Salvar' : 'Prosseguir' }}</button>
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

      $('#form_principal_solicitacaoisencaotaxa').find(':input:visible:first').focus();

      $('#form_principal_solicitacaoisencaotaxa :input').on('input change changeDate dateChanged', function() {
        this.setCustomValidity('');
      });

      $('input[id="extras\[cpf\]"], input[id^="extras\[cpf_"]').each(function() {
        $(this).mask('000.000.000-00');
      });
    });

    $('#form_principal_solicitacaoisencaotaxa').on('submit', function(event) {
      var form_valid = true;

      $('#form_principal_solicitacaoisencaotaxa [required]').each(function () {
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

      if (!form_valid)
        event.preventDefault();
    });

    $('#password').on('input', function () {
      validar_forca_senha($(this).val());
    });
  </script>
@endsection
