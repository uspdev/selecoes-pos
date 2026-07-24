<?php

    $inscricao_ou_matricula = '';
    $objetivo = '';

    if ($selecao->categoria?->nome == 'Aluno Regular') {
        if ($selecao->fazInscricoes()) {
            $inscricao_ou_matricula = 'inscrição';
            $objetivo = 'o processo seletivo ' . $selecao->nome;
        } elseif ($selecao->fazMatriculas()) {
            $inscricao_ou_matricula = 'matrícula';
            $objetivo = 'o programa ' . $selecao->programa?->nomeCompleto();
        }
    } elseif ($selecao->categoria?->nome == 'Aluno Especial') {
        if (Parametro::first()->especiaisFazInscricoes())
            $inscricao_ou_matricula = 'inscrição';
        elseif (Parametro::first()->especiaisFazMatriculas())
            $inscricao_ou_matricula = 'matrícula';
        $objetivo = 'aluno especial';
    }
