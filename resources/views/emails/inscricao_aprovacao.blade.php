@nomenclatura(['selecao' => $inscricao->selecao])

Olá {{ $user->name }},<br />
<br />
Sua inscrição para {{ $objetivo }} foi aceita.<br />
@if (($boleto_momento_envio == 'Aprovação da Inscrição/Matrícula') && ($arquivos_count > 0))
  Não deixe de pagar {{ ($arquivos_count == 1) ? 'o boleto que segue' : 'os boletos que seguem' }} em anexo.<br />
@endif
<br />
@foreach ($arquivos_erro as $arquivo_erro)
  {!! $arquivo_erro !!}<br />
@endforeach
<br />
{{ $inscricao->selecao->email_inscricaoaprovacao_texto }}
<br />
@include('emails.rodape')
