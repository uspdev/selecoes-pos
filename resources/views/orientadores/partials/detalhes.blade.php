@if (count($orientador->selecoes) > 0)
  <a href="#detalhes_{{ \Str::lower($orientador->id) }}" class="btn btn-sm text-primary" data-toggle="collapse" role="button">
    <span class="badge badge-success">{{ count($orientador->selecoes) }} {{ (count($orientador->selecoes) == 1) ? 'seleção' : 'seleções' }}</span>
  </a>
@else
  &nbsp;
  <span class="badge badge-success">0 seleções</span>
@endif

<div class="ml-2 collapse" id="detalhes_{{ \Str::lower($orientador->id) }}">
  <div class="card">
    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <div>
            @if ($orientador->selecoes && $orientador->selecoes->isNotEmpty())
              <b>Seleções</b><br>
              <div class="ml-2">
                @foreach ($orientador->selecoes as $selecao)
                  <a href="selecoes/edit/{{ $selecao->id }}">{{ $selecao->nome }} <i class="fas fa-share"></i></a><br>
                @endforeach
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
