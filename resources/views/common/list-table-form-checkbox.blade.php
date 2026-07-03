<div class="form-group row">
  @php
    $col['label'] .= collect($rules[$col['name']] ?? [])->first(fn($rule) => str_contains($rule, 'required')) ? '&nbsp;<small class="text-required">(*)</small>' : '';
  @endphp
  <div class="col-sm-12 d-flex align-items-center" style="gap: 10px;">
    @php
      $input = html()->input('checkbox', $col['name'])->checked(old($col['name'], $modo == 'edit' ? $objeto->{$col['name']} : ''))->class('form-control')->style('width: auto; margin: 0;');
      if (isset($rules) && (in_array('required', $rules[$col['name']])))
        $input = $input->required();
    @endphp
    {{ $input }}
    {{ html()->label($col['label'] ?? $col['name'], $col['name'])->style('margin: 0;') }}
  </div>
  <br />
</div>
