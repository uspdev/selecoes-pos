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

      $('#categoria_id').change(function () {
        var programa_div = $('#programa_id').closest('.form-group');
        if ($('#categoria_id option:selected').text() !== 'Aluno Especial') {
          programa_div.show();
          updateInscricaoMatriculaLabels('Matrícula', 'Matrículas', 'Inscrição', 'Inscrições');
        } else {
          $('#programa_id option:first').prop('selected', true);
          programa_div.hide();
          @if (Parametro::first()->especiaisFazInscricoes())
            updateInscricaoMatriculaLabels('Matrícula', 'Matrículas', 'Inscrição', 'Inscrições');
          @else
            updateInscricaoMatriculaLabels('Inscrição', 'Inscrições', 'Matrícula', 'Matrículas');
          @endif
        }
      });

      $('#categoria_id').trigger('change');

      $('#programa_id').change(function () {
        var programa_id = $(this).val();
        var programa = $({!! $programas !!}).filter(function(index, item) {
          return item.id == programa_id;
        })[0];
        if (programa)
          if (programa.fazInscricoes)
            updateInscricaoMatriculaLabels('Matrícula', 'Matrículas', 'Inscrição', 'Inscrições');
          else
            updateInscricaoMatriculaLabels('Inscrição', 'Inscrições', 'Matrícula', 'Matrículas');
      });

      $('#programa_id').trigger('change');

      updateCampoFluxoContinuo();
      updateCamposSolicitacoesIsencaoTaxaDataHora();
      updateCamposBoleto();
      updateQuadrosSolicitacoesIsencaoTaxa();

      $('#tem_taxa').on('click', function () {
        updateCampoFluxoContinuo();
        updateCamposSolicitacoesIsencaoTaxaDataHora();
        updateCamposBoleto();
        updateQuadrosSolicitacoesIsencaoTaxa();
      });

      $('#fluxo_continuo').on('click', function () {
        updateCamposSolicitacoesIsencaoTaxaDataHora();
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
      });
    });

    function updateCampoFluxoContinuo() {
      if (!$('#tem_taxa').prop('checked')) {
        $('#fluxo_continuo').prop('checked', false);
        $('#fluxo_continuo').parents('div').eq(1).hide();
      } else
        $('#fluxo_continuo').parents('div').eq(1).show();
    }

    function updateCamposSolicitacoesIsencaoTaxaDataHora() {
      if (!$('#tem_taxa').prop('checked') || $('#fluxo_continuo').prop('checked')) {
        // oculta os campos de data e hora de solicitações de isenção de taxa
        $('#solicitacoesisencaotaxa_data_inicio').val('');
        $('#solicitacoesisencaotaxa_data_inicio').parents('div').eq(1).hide();
        $('#solicitacoesisencaotaxa_hora_inicio').next('.flatpickr-calendar').find('input[type="number"]').val('00');
        $('#solicitacoesisencaotaxa_hora_inicio').parents('div').eq(1).hide();
        $('#solicitacoesisencaotaxa_data_fim').val('');
        $('#solicitacoesisencaotaxa_data_fim').parents('div').eq(1).hide();
        $('#solicitacoesisencaotaxa_hora_fim').next('.flatpickr-calendar').find('input[type="number"]').val('00');
        $('#solicitacoesisencaotaxa_hora_fim').parents('div').eq(1).hide();
      } else {
        // exibe os campos de data e hora de solicitações de isenção de taxa
        $('#solicitacoesisencaotaxa_data_inicio').parents('div').eq(1).show();
        $('#solicitacoesisencaotaxa_hora_inicio').parents('div').eq(1).show();
        $('#solicitacoesisencaotaxa_data_fim').parents('div').eq(1).show();
        $('#solicitacoesisencaotaxa_hora_fim').parents('div').eq(1).show();
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
    }

    function updateQuadrosSolicitacoesIsencaoTaxa() {
      if (!$('#tem_taxa').prop('checked')) {
        $('#card-selecao-formulario-solicitacoesisencaotaxa').hide();
        $('#card-motivosisencaotaxa').hide();
        $('#card-tiposarquivosolicitacaoisencaotaxa').hide();
        $('#card-selecao-solicitacoesisencaotaxa').hide();
      } else {
        $('#card-selecao-formulario-solicitacoesisencaotaxa').show();
        $('#card-motivosisencaotaxa').show();
        $('#card-tiposarquivosolicitacaoisencaotaxa').show();
        $('#card-selecao-solicitacoesisencaotaxa').show();
      }
    }

    function updateInscricaoMatriculaLabels(oldLabel, oldLabelPlural, newLabel, newLabelPlural) {
      if (!$('#form_principal').data('labels-init')) {
        // primeira chamada a este método
        oldLabel = 'Inscrição/Matrícula';
        oldLabelPlural = 'Inscrições/Matrículas';
        $('#form_principal').data('labels-init', true);
      }
      $('label[for="fluxo_continuo"]').text($('label[for="fluxo_continuo"]').text().replace(oldLabelPlural.toLowerCase(), newLabelPlural.toLowerCase()));
      $('label[for="tem_taxa"]').text($('label[for="tem_taxa"]').text().replace(oldLabel, newLabel));
      $('label[for="inscricoesmatriculas_data_inicio"]').html($('label[for="inscricoesmatriculas_data_inicio"]').html().replace(oldLabelPlural, newLabelPlural));
      $('label[for="inscricoesmatriculas_data_fim"]').html($('label[for="inscricoesmatriculas_data_fim"]').html().replace(oldLabelPlural, newLabelPlural));
      $('label[for="email_inscricaomatriculaaprovacao_texto"]').text($('label[for="email_inscricaomatriculaaprovacao_texto"]').text().replace(oldLabel, newLabel));
      $('label[for="email_inscricaomatricularejeicao_texto"]').text($('label[for="email_inscricaomatricularejeicao_texto"]').text().replace(oldLabel, newLabel));
    }
  </script>
@endsection
