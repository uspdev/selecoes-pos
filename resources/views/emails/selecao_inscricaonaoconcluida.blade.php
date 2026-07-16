@nomenclatura

Olá {{ $candidatonome }},<br />
<br />
O período de inscrições {{ $objetivo == 'aluno especial' ? 'para ' . $objetivo : 'd' . $objetivo }} encerra-se em {{ formatarDataHora($selecao->inscricoes_datahora_fim) }}.<br />
Você iniciou sua inscrição, mas não a enviou.<br />
Entre em sua inscrição, envie todos os documentos exigidos e clique no botão "Enviar Inscrição".<br />
Sem isso, ela não será avaliada!<br />
<br />
@include('emails.rodape')
