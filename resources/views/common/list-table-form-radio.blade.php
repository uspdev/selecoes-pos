<div class="form-group row">
  @php
    $col['label'] .= collect($rules[$col['name']] ?? [])->first(fn($rule) => str_contains($rule, 'required')) ? '&nbsp;<small class="text-required">(*)</small>' : '';
    $selectedValue = (string) old($col['name'], $modo == 'edit' ? $objeto->{$col['name']} : '');
  @endphp
  {{ html()->label($col['label'] ?? $col['name'])->class('col-form-label col-sm-3') }}
  <div class="col-sm-9">
    @foreach($col['data'] as $value => $text)
      <div class="form-check mt-2">
        {{ html()->radio($col['name'], $selectedValue === (string) $value, $value)
          ->id($col['name'] . '_' . $loop->index)
          ->class('form-check-input')
        }}
        {{ html()->label($text)->for($col['name'] . '_' . $loop->index)->class('form-check-label') }}
      </div>
    @endforeach
  </div>
</div>
