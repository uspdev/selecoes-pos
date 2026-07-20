@section('styles')
@parent
<style>
  #card-selecao-principal {
    border: 1px solid coral;
    border-top: 3px solid coral;
  }
</style>
@endsection

{{ html()->form('post', $data->url . (($modo == 'edit') ? ('/edit/' . $selecao->id) : '/create'))
  ->attribute('id', 'form_principal')
  ->open() }}
  @csrf
  @method($modo == 'edit' ? 'put' : 'post')
  {{ html()->hidden('id') }}
  <div class="card mb-3 w-100" id="card-selecao-principal">
    <div class="card-header">
      Informações básicas
    </div>
    <div class="card-body">
      <div class="list_table_div_form">
        @include('common.list-table-form-contents')
      </div>
      @if ($condicao_ativa)
        <div class="text-right">
          <button type="submit" class="btn btn-primary">{{ ($modo == 'edit' ) ? 'Salvar' : 'Prosseguir' }}</button>
        </div>
      @endif
    </div>
  </div>
{{ html()->form()->close() }}

@section('javascripts_bottom')
@parent
  <script src="js/functions.js"></script>
  <script type="text/javascript">
    $(document).ready(function() {
      $('#form_principal').find(':input:visible:first').focus();

      $('#form_principal :input').on('input change changeDate dateChanged', function() {
        this.setCustomValidity('');
      });

      $('input[id*="_data_"]').each(function() {
        $(this).mask('00/00/0000');
      });

      permite_taxa = {{ Parametro::first()->permiteTaxa() ? 'true' : 'false' }};

      $('#categoria_id, #programa_id').change(function () {
        if (!permite_taxa)
          $('#tem_taxa').closest('.form-group').hide();

        faz_inscricoes = false;
        faz_matriculas = false;
        if (($('#categoria_id option:selected').text() !== 'Aluno Especial') && ($('#categoria_id').val() !== '')) {
          $('#programa_id').closest('.form-group').show();
          programa_id = $('#programa_id').val();
          programa = $({!! $programas !!}).filter(function(index, item) {
            return item.id == programa_id;
          })[0];
          if (programa) {
            faz_inscricoes = programa.fazInscricoes;
            faz_matriculas = programa.fazMatriculas;
          }
        } else {
          $('#programa_id option:first').prop('selected', true);
          $('#programa_id').closest('.form-group').hide();
          if ($('#categoria_id option:selected').text() === 'Aluno Especial') {
            faz_inscricoes = {{ Parametro::first()->especiaisFazInscricoes() ? 'true' : 'false' }};
            faz_matriculas = {{ Parametro::first()->especiaisFazMatriculas() ? 'true' : 'false' }};
          }
        }

        if (faz_inscricoes)    // quando faz ambas inscrições e matrículas, o campo "tem taxa" recai nas inscrições, então não precisamos checar por faz_inscricoes && faz_matriculas
          updateTemTaxaLabel('Inscrição');
        else if (faz_matriculas)
          updateTemTaxaLabel('Matrícula');

        updateCamposDataHora();
        updateCamposEmail();
        updateCamposBoleto();
      });

      $('#categoria_id').trigger('change');

      updateCampoFluxoContinuo();

      $('#tem_taxa').on('click', function () {
        updateCampoFluxoContinuo();
        updateCamposDataHora();
        updateCamposBoleto();
      });

      $('#fluxo_continuo').on('click', function () {
        updateCamposDataHora();
        updateCamposBoleto();
      });

      $('#form_principal').on('submit', function(event) {
        var form_valid = true;

        $('#form_principal input[id*="_data_"]').each(function () {
          if (!validar_data(this.value)) {
            form_valid = false;
            return mostrar_validacao(this, 'Data inválida');
          }
        });

        if (!form_valid)
          event.preventDefault();
        else if ($('#fluxo_continuo').prop('checked')) {
          if (faz_inscricoes) {
            $('#solicitacoesisencaotaxa_data_inicio').val($('#inscricoes_data_inicio').val());
            $('#solicitacoesisencaotaxa_hora_inicio').val($('#inscricoes_hora_inicio').val());
            $('#solicitacoesisencaotaxa_data_fim').val($('#inscricoes_data_fim').val());
            $('#solicitacoesisencaotaxa_hora_fim').val($('#inscricoes_hora_fim').val());
            if (faz_matriculas) {
              $('#matriculas_data_inicio').val($('#inscricoes_data_inicio').val());
              $('#matriculas_hora_inicio').val($('#inscricoes_hora_inicio').val());
              $('#matriculas_data_fim').val($('#inscricoes_data_fim').val());
              $('#matriculas_hora_fim').val($('#inscricoes_hora_fim').val());
            }
          } else if (faz_matriculas) {
            $('#solicitacoesisencaotaxa_data_inicio').val($('#matriculas_data_inicio').val());
            $('#solicitacoesisencaotaxa_hora_inicio').val($('#matriculas_hora_inicio').val());
            $('#solicitacoesisencaotaxa_data_fim').val($('#matriculas_data_fim').val());
            $('#solicitacoesisencaotaxa_hora_fim').val($('#matriculas_hora_fim').val());
          }
        }
      });
    });

    function updateCampoFluxoContinuo() {
      if (!$('#tem_taxa').prop('checked')) {
        $('#fluxo_continuo').prop('checked', false);
        $('#fluxo_continuo').parents('div').eq(1).hide();
      } else
        $('#fluxo_continuo').parents('div').eq(1).show();
    }

    function ocultaCamposDataHora(classe_nome_plural) {
      $('#' + classe_nome_plural + '_data_inicio').val('').parents('div').eq(1).hide();
      $('#' + classe_nome_plural + '_hora_inicio').next('.flatpickr-calendar').find('input[type="number"]').val('00');
      $('#' + classe_nome_plural + '_hora_inicio').parents('div').eq(1).hide();
      $('#' + classe_nome_plural + '_data_fim').val('').parents('div').eq(1).hide();
      $('#' + classe_nome_plural + '_hora_fim').next('.flatpickr-calendar').find('input[type="number"]').val('00');
      $('#' + classe_nome_plural + '_hora_fim').parents('div').eq(1).hide();
    }

    function mostraCamposDataHora(classe_nome_plural) {
      $('#' + classe_nome_plural + '_data_inicio').parents('div').eq(1).show();
      $('#' + classe_nome_plural + '_hora_inicio').parents('div').eq(1).show();
      $('#' + classe_nome_plural + '_data_fim').parents('div').eq(1).show();
      $('#' + classe_nome_plural + '_hora_fim').parents('div').eq(1).show();
    }

    function atualizaCamposDataHora(classe_nome_plural, newLabel) {
      elementosInicio = $('label[for="' + classe_nome_plural + '_data_inicio"]').children().detach();
      elementosFim = $('label[for="' + classe_nome_plural + '_data_fim"]').children().detach();
      $('label[for="' + classe_nome_plural + '_data_inicio"]').html('Início das ' + newLabel + '&nbsp;').append(elementosInicio);
      $('label[for="' + classe_nome_plural + '_data_fim"]').html('Fim das ' + newLabel + '&nbsp;').append(elementosFim);
    }

    function updateCamposDataHora() {
      if ($('#fluxo_continuo').prop('checked')) {
        ocultaCamposDataHora('solicitacoesisencaotaxa');
        if (faz_inscricoes && faz_matriculas) {
          atualizaCamposDataHora('inscricoes', 'Solicitações de Isenção de Taxa, Inscrições e Matrículas');
          ocultaCamposDataHora('matriculas');
        } else if (faz_inscricoes) {
          atualizaCamposDataHora('inscricoes', 'Solicitações de Isenção de Taxa e Inscrições');
          mostraCamposDataHora('inscricoes');
          ocultaCamposDataHora('matriculas');
        } else if (faz_matriculas) {
          atualizaCamposDataHora('matriculas', 'Solicitações de Isenção de Taxa e Matrículas');
          ocultaCamposDataHora('inscricoes');
          mostraCamposDataHora('matriculas');
        }
      } else {
        atualizaCamposDataHora('inscricoes', 'Inscrições');
        atualizaCamposDataHora('matriculas', 'Matrículas');
        if ($('#tem_taxa').prop('checked'))
          mostraCamposDataHora('solicitacoesisencaotaxa');
        else
          ocultaCamposDataHora('solicitacoesisencaotaxa');
        if (faz_inscricoes)
          mostraCamposDataHora('inscricoes');
        else
          ocultaCamposDataHora('inscricoes');
        if (faz_matriculas)
          mostraCamposDataHora('matriculas');
        else
          ocultaCamposDataHora('matriculas');
      }
    }

    function updateCamposBoleto() {
      if (!$('#tem_taxa').prop('checked')) {
        // oculta os campos de boleto
        $('#boleto_data_vencimento').val('');
        $('#boleto_data_vencimento').parents('div').eq(1).hide();
        $('#boleto_offset_vencimento').val('');
        $('#boleto_offset_vencimento').parents('div').eq(1).hide();
        $('#boleto_valor').val('');
        $('#boleto_valor').parents('div').eq(1).hide();
        $('#boleto_texto').val('');
        $('#boleto_texto').parents('div').eq(1).hide();
      } else {
        // exibe os campos de boleto
        if ($('#fluxo_continuo').prop('checked')) {
          $('#boleto_data_vencimento').val('');
          $('#boleto_data_vencimento').parents('div').eq(1).hide();
          $('#boleto_offset_vencimento').parents('div').eq(1).show();
        } else {
          $('#boleto_data_vencimento').parents('div').eq(1).show();
          $('#boleto_offset_vencimento').val('');
          $('#boleto_offset_vencimento').parents('div').eq(1).hide();
        }
        $('#boleto_valor').parents('div').eq(1).show();
        $('#boleto_texto').parents('div').eq(1).show();
      }

      // reposiciona o campo de data de vencimento do boleto, conforme for o caso
      if ((!$('#fluxo_continuo').prop('checked')) && ($('#tem_taxa').prop('checked')) && faz_inscricoes && faz_matriculas)
        $('#boleto_data_vencimento').parents('div').eq(1).insertBefore($('#matriculas_data_inicio').parents('div').eq(1));
      else
        $('#boleto_data_vencimento').parents('div').eq(1).insertAfter($('#matriculas_hora_fim').parents('div').eq(1));
    }

    function updateTemTaxaLabel(newLabel) {
      oldLabel = '';
      if ($('label[for="tem_taxa"]').text().includes('Inscrição'))
          oldLabel = 'Inscrição';
      else if ($('label[for="tem_taxa"]').text().includes('Matrícula'))
          oldLabel = 'Matrícula';
      if (oldLabel !== '')
        $('label[for="tem_taxa"]').text($('label[for="tem_taxa"]').text().replace(oldLabel, newLabel));
    }

    function ocultaCamposEmail(classe_nome) {
      $('#email_' + classe_nome + 'aprovacao_texto').val('').parents('div').eq(1).hide();
      $('#email_' + classe_nome + 'rejeicao_texto').val('').parents('div').eq(1).hide();
    }

    function mostraCamposEmail(classe_nome) {
      $('#email_' + classe_nome + 'aprovacao_texto').parents('div').eq(1).show();
      $('#email_' + classe_nome + 'rejeicao_texto').parents('div').eq(1).show();
    }

    function updateCamposEmail() {
      if (faz_inscricoes)
        mostraCamposEmail('inscricao');
      else
        ocultaCamposEmail('inscricao');
      if (faz_matriculas)
        mostraCamposEmail('matricula');
      else
        ocultaCamposEmail('matricula');
    }
  </script>
@endsection
