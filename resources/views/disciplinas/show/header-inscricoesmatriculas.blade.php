<div class="d-flex">
  <b>
    {{ $objeto_disciplina->sigla }} - {{ $objeto_disciplina->nome }}
  </b>
  <div class="hidden-btn d-none ml-auto">
    @if (in_array($objeto->selecao->estado, ['Período de Solicitações de Isenção de Taxa e de Inscrições/Matrículas', 'Período de Inscrições/Matrículas']) && (session('perfil') == 'usuario'))
      @include('common.btn-delete-sm', ['action' => $classe_nome_plural . '/' . $objeto->id . '/disciplinas/' . $objeto_disciplina->id])
    @endif
  </div>
</div>

@once
@section('javascripts_bottom')
@parent
  <script type="text/javascript">
    $(function() {
      $('.disciplina-item').hover(
        function() {
          $(this).find('.hidden-btn').removeClass('d-none');
        },
        function() {
          $(this).find('.hidden-btn').addClass('d-none');
        }
      );
    });
  </script>
@endsection
@endonce
