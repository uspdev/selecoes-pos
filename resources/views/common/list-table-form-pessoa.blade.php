<div class="form-group row">
  @php
    $col['label'] .= collect($rules[$col['name']] ?? [])->first(fn($rule) => str_contains($rule, 'required')) ? '&nbsp;<small class="text-required">(*)</small>' : '';
  @endphp
  {{ html()->label($col['label'] ?? $col['name'])->for($col['name'])->class('col-form-label col-sm-3') }}
  <div class="col-sm-9">
    {{ html()->select($col['name'], [])
      ->class('form-control')
      ->placeholder('Selecione...')
    }}
  </div>
</div>
