<div class="form-group row">
  @php
    $col['label'] .= collect($rules[$col['name']] ?? [])->first(fn($rule) => str_contains($rule, 'required')) ? '&nbsp;<small class="text-required">(*)</small>' : '';
  @endphp
  {{ html()->label($col['label'] ?? $col['name'], $col['name'])->class('col-form-label col-sm-3')->style('margin-top: -20px;') }}
  <div class="col-sm-3" style="margin-top: -20px;">
    @php
      $input = html()->input('password', $col['name'])->value(old($col['name'], $modo == 'edit' ? $objeto->{$col['name']} : ''))->class('form-control')->style('width: 100%; padding-right: 30px;');
      if (isset($rules) && (in_array('required', $rules[$col['name']])))
        $input = $input->required();
    @endphp
    {{ $input }}
    <a href="javascript:void(0);" onclick="toggle_password('{{ $col['name'] }}')" style="position: absolute; right: 24px; top: 20%; text-decoration: none;">
      <img src="/images/view.png" id="toggle_icon_{{ $col['name'] }}" style="width: 20px; height: 20px;">
    </a>
  </div>
  <div id="strength-wrapper">
    <div id="barra_forca_password" style="height: 10px; width: 0px;">&nbsp;</div>
    <p id="texto_forca_password" style="margin-top: 5px;">&nbsp;</p>
  </div>
</div>

@section('javascripts_bottom')
@parent
  <script src="js/functions.js"></script>
  <script type="text/javascript">
    $('#{{ $col['name'] }}').on('input', function () {
      validar_forca_senha($(this).val());
    });
  </script>
@endsection
