<div class="form-group row">
  @php
    $col['label'] .= collect($rules[$col['name']] ?? [])->first(fn($rule) => str_contains($rule, 'required')) ? '&nbsp;<small class="text-required">(*)</small>' : '';
    $colSize = collect($col['data'])->every(fn($value) => strlen((string) $value) <= 12) ? 3 : 9;
  @endphp
  {{ html()->label($col['label'] ?? $col['name'])->for($col['name'])->class('col-form-label col-sm-3') }}
  <div class="col-sm-{{ $colSize }}">
    {{ html()->select($col['name'], $col['data'])
      ->value(old($col['name'], $modo == 'edit' ? $objeto->{$col['name']} : ''))
      ->class('form-control')
      ->placeholder('Selecione...')
    }}
  </div>
</div>
