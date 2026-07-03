@section('styles')
@parent
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.css" />
  <link rel="stylesheet" href="css/arquivos.css">
  <style>
    #card-arquivos {
      border: 1px solid DarkGoldenRod;
      border-top: 3px solid DarkGoldenRod;
    }
  </style>
@endsection

{{ html()->form('post', $data->url . '/edit/' . $objeto->id)
  ->attribute('enctype', 'multipart/form-data')
  ->attribute('id', 'form_arquivos')
  ->open() }}
  @csrf
  @method('put')
  {{ html()->hidden('id') }}
  <a id="card_arquivos" name="card_arquivos"></a>
  <div class="card bg-light mb-3 w-100" id="card-arquivos">
    <div class="card-header form-inline">
      @if ($classe_nome == 'Selecao')
        Informativos
      @else
        Documentos
      @endif
      <span data-toggle="tooltip" data-html="true" title="Tamanho máximo de cada arquivo: {{ $max_upload_size }}KB ">
        <i class="fas fa-question-circle text-secondary ml-2"></i>
      </span>
      @if ($objeto->arquivos->count() > 0)
        <span class="btn btn-sm btn-light text-primary ml-2" onclick="baixar_todos_arquivos('arquivos/ziptodosdoobjeto/{{ $classe_nome }}/{{ $objeto->id }}', 'arquivos/downloadtodosdoobjeto/{{ $classe_nome }}/{{ $objeto->id }}')"> <i class="fas fa-download"></i> Baixar Todos</span>
      @endif
    </div>
    <div class="card-body">
      <input type="hidden" name="classe_nome" value="{{ $classe_nome }}">
      <input type="hidden" name="objeto_id" value="{{ $objeto->id }}">
      <input type="hidden" name="tipoarquivo" id="tipoarquivo">
      <input type="hidden" name="nome_arquivo" id="nome_arquivo">
      @php
        $i = 0;
      @endphp
      @foreach ($objeto->tiposarquivo->where('classe_nome', $tipoarquivo_classe_nome_plural_acentuado) as $tipoarquivo)
        @if (($tipoarquivo['nome'] !== 'Boleto(s) de Pagamento - Disciplinas Removidas') || ($objeto->arquivos->where('pivot.tipo', $tipoarquivo['nome'])->count() > 0))    {{-- desconsidera o tipo de documento de boletos para disciplinas removidas quando não há nenhum boleto para disciplina removida --}}
          @if (!($solicitacaoisencaotaxa_aprovada ?? false) || !str_starts_with($tipoarquivo['nome'], 'Boleto(s) de Pagamento'))    {{-- desconsidera os tipos de documento de boletos caso haja solicitação de isenção de taxa aprovada --}}
            <div class="arquivos-lista">
              {{ $tipoarquivo['nome'] }}&nbsp;{!! ($tipoarquivo->isObrigatorio($objeto->extras) ? '<small class="text-required">(*)</small>' : '') !!}
              @php
                $editavel = (isset($tipoarquivo['editavel']) && $tipoarquivo['editavel']);
                if (session('perfil') == 'usuario')
                  if ($classe_nome == 'SolicitacaoIsencaoTaxa')
                    $editavel &= in_array($selecao->estado, ['Período de Solicitações de Isenção de Taxa e de Inscrições/Matrículas', 'Período de Solicitações de Isenção de Taxa']);
                  elseif (in_array($classe_nome, ['Inscricao', 'Matricula']))
                    $editavel &= in_array($selecao->estado, ['Período de Solicitações de Isenção de Taxa e de Inscrições/Matrículas', 'Período de Inscrições/Matrículas']);
              @endphp
              @if (Gate::allows($classe_nome_plural . '.updateArquivos', $objeto) && $editavel)
                <label for="input_arquivo_{{ $i }}">
                  <span class="btn btn-sm btn-light text-primary ml-2"> <i class="fas fa-plus"></i> Adicionar</span>
                </label>
              @endif
              @canany(['perfiladmin', 'perfilgerente'])
                @if (($tipoarquivo['nome'] === 'Boleto(s) de Pagamento') && (
                  (($boleto_momento_envio === 'Envio da Inscrição/Matrícula') && ($objeto->estado !== 'Aguardando Envio')) ||
                  (($boleto_momento_envio === 'Aprovação da Inscrição/Matrícula') && ($objeto->estado === 'Aprovada'))
                ))    {{-- se o tipo de documento é boleto e ele(s) já deve(m) ter sido gerado(s) e enviado(s) --}}
                  @if (($objeto->selecao->categoria->nome !== 'Aluno Especial') && ($objeto->arquivos->where('pivot.tipo', 'Boleto(s) de Pagamento')->count() == 0))    {{-- se é aluno regular e não tem o devido boleto --}}
                    <a onclick="gerar_boletos({{ $objeto->id }}); return false;" class="btn btn-sm btn-light text-primary ml-2">
                      <i class="fas fa-plus"></i> Gerar
                    </a>
                  @elseif (($objeto->selecao->categoria->nome == 'Aluno Especial') && (count($objeto->disciplinas_sem_boleto) > 0))    {{-- se é aluno especial e há boleto(s) a ser(em) gerado(s) para sua(s) disciplina(s) --}}
                    @include('disciplinas.partials.modal-boletos', ['inclusor_url' => $classe_nome_plural . '/geraboletos/' . $objeto->id])
                  @endif
                @endif
              @endcan
              <input type="hidden" id="tipoarquivo_{{ $i }}" value="{{ $tipoarquivo['nome'] }}">
              <input type="file" name="arquivo[]" id="input_arquivo_{{ $i }}" accept="application/pdf, .pdf" class="d-none" multiple>

              @if ($objeto->arquivos->where('pivot.tipo', $tipoarquivo['nome'])->count() > 0)
                <ul class="list-unstyled">
                  @foreach ($objeto->arquivos->where('pivot.tipo', $tipoarquivo['nome']) as $arquivo)
                    @if (preg_match('/^(application\/pdf|image\/png|image\/jpeg)$/i', $arquivo->mimeType))
                      <li class="modo-visualizacao">
                        @if (Gate::allows($classe_nome_plural . '.updateArquivos', $objeto) && $editavel)
                          <div class="arquivo-acoes uma-acao d-inline-block">
                            <a onclick="excluir_arquivo({{ $arquivo->id }}, '{{ $arquivo->nome_original }}'); return false;" class="btn btn-outline-danger btn-sm btn-deletar btn-arquivo-acao">
                              <i class="far fa-trash-alt"></i>
                            </a>
                          </div>
                        @endif
                        @canany(['perfiladmin', 'perfilgerente'])
                          @if ($tipoarquivo['nome'] === 'Boleto(s) de Pagamento')
                            <div class="arquivo-acoes uma-acao d-inline-block">
                              <a onclick="enviar_boleto({{ $objeto->id }}, {{ $arquivo->id }});" class="btn btn-outline-warning btn-sm btn-enviar btn-arquivo-acao">
                                <i class="far fa-paper-plane"></i>
                              </a>
                            </div>
                          @endif
                        @endcan
                        <a href="arquivos/{{ $arquivo->id }}" title="{{ $arquivo->nome_original }}" class="nome-arquivo-display"><i class="fas fa-file-pdf"></i>
                          <span>{{ $arquivo->nome_original }}</span>
                        </a>
                      </li>
                    @endif
                  @endforeach
                </ul>
              @endif
            </div>
            @php
              $i++;
            @endphp
          @endif
        @endif
      @endforeach
    </div>
  </div>
{{ html()->form()->close() }}

@once
  @include('common.modal-processando')
@endonce

@section('javascripts_bottom')
  @parent
  @include('partials.scripts-arquivos')
@endsection
