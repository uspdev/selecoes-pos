<?php

  if ($selecao->categoria->nome == 'Aluno Especial') {
    $inscricao_ou_matricula = 'matrícula';
    $inscricao_ou_matricula_plural = 'matrículas';
    $objetivo = 'aluno especial';
  } elseif ($selecao->programa->fazMatriculas()) {
    $inscricao_ou_matricula = 'matrícula';
    $inscricao_ou_matricula_plural = 'matrículas';
    $objetivo = 'o programa ' . $selecao->programa->nomeCompleto();
  } elseif ($selecao->programa->fazInscricoes()) {
    $inscricao_ou_matricula = 'inscrição';
    $inscricao_ou_matricula_plural = 'inscrições';
    $objetivo = 'o processo seletivo ' . $selecao->nome;
  } else {
    $inscricao_ou_matricula = '';
    $inscricao_ou_matricula_plural = '';
    $objetivo = '';
  }
