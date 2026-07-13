@nomenclatura(['selecao' => $inscricao->selecao])

Olá {{ $user->name }},<br />
<br />
Você reenviou sua inscrição para {{ $objetivo }}.<br />
@if (($boleto_momento_envio == 'Envio da Inscrição/Matrícula') && ($arquivos_count > 0))
  Pelo fato de você ter incluído e/ou removido disciplinas, o sistema gerou novo(s) boleto(s) para pagamento.<br />
  Não deixe de pagar {{ ($arquivos_count == 1) ? 'o boleto que segue' : 'os boletos que seguem' }} em anexo.<br />
@endif
<br />
@foreach ($arquivos_erro as $arquivo_erro)
  {!! $arquivo_erro !!}<br />
@endforeach
@include('emails.rodape')
