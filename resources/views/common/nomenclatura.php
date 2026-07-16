<?php

    $inscricao_ou_matricula = '';
    $objetivo = '';

    if ($selecao->categoria->nome != 'Aluno Especial') {
        if ($selecao->programa->fazInscricoes()) {
            $inscricao_ou_matricula = 'inscrição';
            $objetivo = 'o processo seletivo ' . $selecao->nome;
        } elseif ($selecao->programa->fazMatriculas()) {
            $inscricao_ou_matricula = 'matrícula';
            $objetivo = 'o programa ' . $selecao->programa->nomeCompleto();
        }
    } else {
        if (Parametro::first()->especiaisFazInscricoes())
            $inscricao_ou_matricula = 'inscrição';
        elseif (Parametro::first()->especiaisFazMatriculas())
            $inscricao_ou_matricula = 'matrícula';
        $objetivo = 'aluno especial';
    }
