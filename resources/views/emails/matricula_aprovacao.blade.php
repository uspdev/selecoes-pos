@nomenclatura(['selecao' => $matricula->selecao])

Olá {{ $user->name }},<br />
<br />
Sua matrícula para {{ $objetivo }} foi aceita.<br />
@if (($boleto_momento_envio == 'Aprovação da Inscrição/Matrícula') && ($arquivos_count > 0))
  Não deixe de pagar {{ ($arquivos_count == 1) ? 'o boleto que segue' : 'os boletos que seguem' }} em anexo.<br />
@endif
<br />
@foreach ($arquivos_erro as $arquivo_erro)
  {!! $arquivo_erro !!}<br />
@endforeach
<br />
{{ $matricula->selecao->email_matriculaaprovacao_texto }}
<br />
@include('emails.rodape')
