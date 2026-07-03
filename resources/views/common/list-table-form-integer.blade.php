<div class="form-group row">
  @php
    $col['label'] .= collect($rules[$col['name']] ?? [])->first(fn($rule) => str_contains($rule, 'required')) ? '&nbsp;<small class="text-required">(*)</small>' : '';
  @endphp
  {{ html()->label($col['label'] ?? $col['name'], $col['name'])->class('col-form-label col-sm-3') }}
  <div class="col-sm-2">
    {{ html()->input('number', $col['name'])
      ->value(old($col['name'], $modo == 'edit' ? $objeto->{$col['name']} : ''))
      ->class('form-control')
      ->attribute('oninput', 'validateInteger(this)')
    }}
  </div>
</div>
