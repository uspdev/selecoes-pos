@nomenclatura(['selecao' => $matricula->selecao])

Olá {{ $user->name }},<br />
<br />
Lamentamos, mas sua matrícula para {{ $objetivo }} foi rejeitada.<br />
<br />
{{ $matricula->selecao->email_matricularejeicao_texto }}
<br />
@include('emails.rodape')
