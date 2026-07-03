@section('styles')
@parent
  <style>
    #card-envio {
      border: 1px solid coral;
      border-top: 3px solid coral;
    }
  </style>
@endsection

@nomenclatura(['selecao' => $objeto->selecao])

@php
  $objeto_aprovada = ($objeto->estado === 'Aprovada');
@endphp

{{ html()->form('post', $data->url . '/edit/' . $objeto->id)
  ->attribute('id', 'form_envio')
  ->attribute('novalidate', '')          // pois faço minha validação manual em $('#form_envio').on('submit'
  ->open() }}
  @csrf
  @method('put')
  {{ html()->hidden('id') }}
  <input type="hidden" id="acao" name="acao" value="envio">
  <div class="card mb-3 w-100" id="card-envio">
    <div class="card-header">
      Envio
    </div>
    <div class="card-body">
      <div class="list_table_div_form">
        <div class="form-group row">
          <div class="col-sm-12 d-flex align-items-center" style="gap: 10px;">
            <input class="form-control" style="width: auto; margin: 0;" name="declaro" id="declaro" type="checkbox" required {{ $objeto_aprovada ? 'checked disabled' : '' }}>
            <label style="margin: 0;" for="declaro">Declaro que as informações prestadas são verdadeiras e assumo inteira responsabilidade pelas mesmas.&nbsp;<small class="text-required">(*)</small></label>
          </div>
          <br />
        </div>
      </div>
      @if (!$objeto_aprovada)
        <div class="text-right">
          <button type="submit" class="btn btn-primary">Enviar {{ ucfirst(explode(' ', \App\Utils\ClasseUtils::obterClasseNomeFormatada($classe_nome))[0]) }}</button>
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

      $('#form_envio :input').on('input change changeDate dateChanged', function() {
        this.setCustomValidity('');
      });
    });

    $('#form_envio').on('submit', function(event) {
      var form_valid = true;

      $('#form_envio [required]').each(function () {
        if (!this.validity.valid) {
          form_valid = false;
          switch (this.type) {
            case 'checkbox':
              if (!this.checked)
                return mostrar_validacao(this, 'Favor marcar esta opção');
          }
        }
      });

      if (!form_valid)
        event.preventDefault();
    });
  </script>
@endsection
