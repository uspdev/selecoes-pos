<div class="form-group row">
  @php
    $col['label'] .= collect($rules[$col['name']] ?? [])->first(fn($rule) => str_contains($rule, 'required')) ? '&nbsp;<small class="text-required">(*)</small>' : '';
  @endphp
  {{ html()->label($col['label'] ?? $col['name'], $col['name'])->class('col-form-label col-sm-3') }}
  <div class="col-sm-2">
    {{ html()->input('text', $col['name'])
      ->value(old($col['name'], $modo == 'edit' ? str_replace('.', ',', $objeto->{$col['name']}) : ''))
      ->class('form-control')
      ->attribute('oninput', 'validateNumber(this)')
    }}
  </div>
</div>
