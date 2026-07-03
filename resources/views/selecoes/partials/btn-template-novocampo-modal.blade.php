<button type="button" class="btn btn-primary btn-sm" onclick="json_modal_form()">
  <i class="fas fa-plus"></i> Adicionar Campo
</button>

<!-- Modal -->
<div class="modal fade" id="json-modal-form" data-backdrop="static" tabindex="-1" aria-labelledby="modalShowJson" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalShowJson">Adicionar novo campo</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="list_table_div_form">
          {{ html()->form('post', route('selecoes.storetemplate', $selecao->id))->id('template-form')->open() }}
            @method('post')
            @csrf
            {{ html()->hidden('id') }}
            <div id="template-new" class="form-group row mt-2">
              <div class="col-3"><strong>Campo</strong></div>
              <input class="form-control col-8" name="campo" id="id_campo1">
            </div>
            @foreach ($selecao->getTemplateFields() as $field)
              <div class="form-group row mt-2">
                <div class="col-3"><strong>{{ ucfirst($field) }}</strong></div>
                @switch($field)
                  @case('type')
                    <select class="form-control col-8" name="new[{{ $field }}]">
                      <option value='text'>Texto</option>
                      <option value='select'>Caixa de Seleção</option>
                      <option value='date'>Data</option>
                      <option value='number'>Número</option>
                      <option value='radio'>Botão de Opção</option>
                      <option value='checkbox'>Caixa de Verificação</option>
                      <option value='label'>Informativo</option>
                    </select>
                    @break
                  @case('validate')
                    <select class="form-control col-8" name="new[{{ $field }}]">
                      <option value=''>Sem validação</option>
                      <option value='required'>Obrigatório</option>
                      <option value='required|integer'>Obrigatório - Somente números</option>
                    </select>
                    @break
                  @case('can')
                    <select class="form-control col-8" name="new[{{ $field }}]">
                      <option value=''>Exibido para todos</option>
                      <option value='gerente'>Somente Gerentes</option>
                    </select>
                    @break
                  @default
                    <input class="form-control col-8" name="new[{{ $field }}]">
                @endswitch
              </div>
            @endforeach
            <div class="text-right mt-2">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
              <button class="btn btn-primary ml-1" type="submit">Salvar</button>
            </div>
          {{ html()->form()->close() }}
        </div>
      </div>
    </div>
  </div>
</div>

@section('javascripts_bottom')
@parent
  <script type="text/javascript">
    $(document).ready(function() {

      $('#json-modal-form').on('shown.bs.modal', function() {
        $('#id_campo1').focus();
      });

      json_modal_form = function() {
        $('#json-modal-form').modal();
      };
    });
  </script>
@endsection
