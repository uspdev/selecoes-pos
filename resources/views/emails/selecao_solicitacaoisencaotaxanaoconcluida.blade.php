@nomenclatura

Olá {{ $candidatonome }},<br />
<br />
O período de solicitações de isenção de taxa {{ $objetivo == 'aluno especial' ? 'para ' . $objetivo : 'd' . $objetivo }} encerra-se em {{ formatarDataHora($selecao->solicitacoesisencaotaxa_datahora_fim) }}.<br />
Você iniciou sua solicitação, mas não a enviou.<br />
Entre em sua solicitação, envie todos os documentos exigidos e clique no botão "Enviar Solicitação".<br />
Sem isso, ela não será avaliada!<br />
<br />
@include('emails.rodape')
